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

        return min(100, $score);
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
     * Las columnas released_at y attributes llegan en la F1-01; hasta entonces getAttribute() devuelve null y no suma.
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
