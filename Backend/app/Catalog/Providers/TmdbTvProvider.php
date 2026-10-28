<?php

namespace App\Catalog\Providers;

/**
 * Proveedor de la categoría `series` con TMDB (external_id `tv:{id}`).
 */
final class TmdbTvProvider extends AbstractTmdbProvider
{
    public const CATEGORY_SLUG = 'series';

    /** Máximo de creadores y de cadenas (por coherencia con la dirección de las películas). */
    public const MAX_NAMES = 3;

    /** Estados de TMDB traducidos; un estado desconocido no se guarda (nunca en inglés). */
    public const STATUS_LABELS = [
        'Returning Series' => 'En emisión',
        'In Production' => 'En producción',
        'Planned' => 'Anunciada',
        'Pilot' => 'Piloto',
        'Ended' => 'Finalizada',
        'Canceled' => 'Cancelada',
        'Cancelled' => 'Cancelada',
    ];

    /** attribute_schema de `series` (lo siembra la F1-10 en categories.attribute_schema). */
    public const ATTRIBUTE_SCHEMA = [
        ['key' => 'year', 'label' => 'Año', 'type' => 'integer', 'filterable' => true, 'show_in_card' => true],
        ['key' => 'genres', 'label' => 'Géneros', 'type' => 'tags', 'filterable' => true, 'show_in_card' => true],
        ['key' => 'seasons', 'label' => 'Temporadas', 'type' => 'integer', 'filterable' => false, 'show_in_card' => false],
        ['key' => 'episodes', 'label' => 'Episodios', 'type' => 'integer', 'filterable' => false, 'show_in_card' => false],
        ['key' => 'creators', 'label' => 'Creación', 'type' => 'tags', 'filterable' => false, 'show_in_card' => false],
        ['key' => 'networks', 'label' => 'Cadenas', 'type' => 'tags', 'filterable' => false, 'show_in_card' => false],
        ['key' => 'status', 'label' => 'Estado', 'type' => 'string', 'filterable' => false, 'show_in_card' => false],
    ];

    protected function mediaType(): string
    {
        return 'tv';
    }

    protected function categorySlug(): string
    {
        return self::CATEGORY_SLUG;
    }

    protected function titleField(): string
    {
        return 'name';
    }

    protected function originalTitleField(): string
    {
        return 'original_name';
    }

    protected function releaseDateField(): string
    {
        return 'first_air_date';
    }

    protected function detailAppends(): string
    {
        return 'external_ids';
    }

    protected function newListPaths(): array
    {
        return ['tv/on_the_air', 'tv/popular'];
    }

    protected function detailAttributes(array $payload): array
    {
        return [
            'seasons' => $this->positiveInt($payload['number_of_seasons'] ?? null),
            'episodes' => $this->positiveInt($payload['number_of_episodes'] ?? null),
            'creators' => $this->names($payload['created_by'] ?? [], self::MAX_NAMES),
            'networks' => $this->names($payload['networks'] ?? [], self::MAX_NAMES),
            'status' => is_string($payload['status'] ?? null) ? (self::STATUS_LABELS[$payload['status']] ?? null) : null,
        ];
    }
}
