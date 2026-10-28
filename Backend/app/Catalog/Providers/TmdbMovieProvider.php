<?php

namespace App\Catalog\Providers;

/**
 * Proveedor de la categoría `peliculas` con TMDB (external_id `movie:{id}`).
 */
final class TmdbMovieProvider extends AbstractTmdbProvider
{
    public const CATEGORY_SLUG = 'peliculas';

    public const MAX_DIRECTORS = 3;

    /** attribute_schema de `peliculas` (lo siembra la F1-10 en categories.attribute_schema). */
    public const ATTRIBUTE_SCHEMA = [
        ['key' => 'year', 'label' => 'Año', 'type' => 'integer', 'filterable' => true, 'show_in_card' => true],
        ['key' => 'genres', 'label' => 'Géneros', 'type' => 'tags', 'filterable' => true, 'show_in_card' => true],
        ['key' => 'runtime', 'label' => 'Duración', 'type' => 'minutes', 'filterable' => false, 'show_in_card' => false],
        ['key' => 'directors', 'label' => 'Dirección', 'type' => 'tags', 'filterable' => false, 'show_in_card' => false],
        ['key' => 'countries', 'label' => 'Países', 'type' => 'tags', 'filterable' => false, 'show_in_card' => false],
        ['key' => 'original_language', 'label' => 'Idioma original', 'type' => 'string', 'filterable' => false, 'show_in_card' => false],
    ];

    protected function mediaType(): string
    {
        return 'movie';
    }

    protected function categorySlug(): string
    {
        return self::CATEGORY_SLUG;
    }

    protected function titleField(): string
    {
        return 'title';
    }

    protected function originalTitleField(): string
    {
        return 'original_title';
    }

    protected function releaseDateField(): string
    {
        return 'release_date';
    }

    protected function detailAppends(): string
    {
        return 'external_ids,credits';
    }

    protected function newListPaths(): array
    {
        return ['movie/now_playing', 'movie/upcoming', 'movie/popular'];
    }

    protected function detailAttributes(array $payload): array
    {
        $crew = data_get($payload, 'credits.crew');
        $directors = [];

        foreach (is_array($crew) ? $crew : [] as $member) {
            if (is_array($member) && ($member['job'] ?? null) === 'Director') {
                $directors[] = $member;
            }
        }

        return [
            'runtime' => $this->positiveInt($payload['runtime'] ?? null),
            'directors' => $this->names($directors, self::MAX_DIRECTORS),
            'countries' => $this->countryNames($payload['production_countries'] ?? []),
            'original_language' => $this->languageName($payload['original_language'] ?? null),
        ];
    }
}
