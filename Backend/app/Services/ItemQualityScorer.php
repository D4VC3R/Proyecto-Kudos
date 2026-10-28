<?php

namespace App\Services;

use App\Models\Item;

/**
 * Calcula la calidad de la ficha de un ítem (quality_score, 0–100), F0-05.
 *
 * Reglas:
 * - +40 si tiene al menos una imagen (una entrada de `images` con `path` o `variants`).
 * - +40 si la descripción tiene 60 caracteres o más y no es de plantilla
 *   (no contiene ninguno de config('catalog.template_description_patterns'), sin distinguir mayúsculas).
 * - +20 si tiene fecha de estreno (`released_at`) o atributos (`attributes`) no vacíos.
 * - Sin descripción (null, vacía o solo espacios), la puntuación se limita a min_quality_for_discovery − 1 (F1-04):
 *   esas fichas nunca salen en la cola ni en los destacados.
 *
 * Los ítems por debajo de config('catalog.min_quality_for_discovery') no salen en la cola de votación
 * ni en los destacados, pero siguen en Explorar y en la búsqueda.
 */
class ItemQualityScorer
{
    private const IMAGE_POINTS = 40;
    private const DESCRIPTION_POINTS = 40;
    private const METADATA_POINTS = 20;
    private const MIN_DESCRIPTION_LENGTH = 60;

    /**
     * Devuelve la puntuación de calidad (0–100) del ítem.
     */
    public function score(Item $item): int
    {
        $score = 0;

        if ($this->hasImage($item->images)) {
            $score += self::IMAGE_POINTS;
        }

        if ($this->hasUsefulDescription($item->description)) {
            $score += self::DESCRIPTION_POINTS;
        }

        if ($this->hasMetadata($item)) {
            $score += self::METADATA_POINTS;
        }

        $score = min(100, $score);

        // F1-04: sin descripción (null, vacía o solo espacios) la ficha nunca llega al umbral de la cola y los
        // destacados, tenga lo que tenga. El tope sigue al umbral configurado; si el umbral cambia,
        // items:recalculate-quality (semanal) lo vuelve a aplicar a los ítems guardados.
        if ($this->isBlankDescription($item->description)) {
            $score = min($score, max(0, (int) config('catalog.min_quality_for_discovery', 40) - 1));
        }

        return $score;
    }

    /**
     * Una descripción null, vacía o solo con espacios cuenta como "sin descripción".
     */
    private function isBlankDescription(?string $description): bool
    {
        return trim((string) $description) === '';
    }

    /**
     * Una imagen cuenta si es un array con `path` o `variants` no vacíos.
     */
    private function hasImage(mixed $images): bool
    {
        if (! is_array($images)) {
            return false;
        }

        foreach ($images as $image) {
            if (is_array($image) && (! empty($image['path']) || ! empty($image['variants']))) {
                return true;
            }
        }

        return false;
    }

    /**
     * La descripción suma si es lo bastante larga y no contiene ningún patrón de plantilla.
     */
    private function hasUsefulDescription(?string $description): bool
    {
        $d = trim((string) $description);

        if (mb_strlen($d) < self::MIN_DESCRIPTION_LENGTH) {
            return false;
        }

        foreach ((array) config('catalog.template_description_patterns', []) as $pattern) {
            if ($pattern !== '' && $pattern !== null && mb_stripos($d, (string) $pattern) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Suma si hay fecha de estreno (released_at) o atributos (attributes), columnas del catálogo (F1-01).
     * No se usa $item->attributes: choca con la propiedad interna de Eloquent.
     */
    private function hasMetadata(Item $item): bool
    {
        if ($item->getAttribute('released_at') !== null) {
            return true;
        }

        $attrs = $item->getAttribute('attributes');
        if (is_string($attrs)) {
            $attrs = json_decode($attrs, true);
        }

        return is_array($attrs) && $attrs !== [];
    }
}
