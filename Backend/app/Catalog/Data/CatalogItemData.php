<?php

namespace App\Catalog\Data;

/**
 * DTO normalizado e inmutable que entregan los proveedores. Nunca contiene el payload en bruto de la fuente
 * (términos de TMDB).
 *
 * @param  array<string, mixed>  $attributes
 * @param  list<array{url: string, role: 'poster'|'banner', license: ?string, author: ?string, source_url: ?string}>  $images
 */
final readonly class CatalogItemData
{
    public function __construct(
        public string $provider,          // 'tmdb'
        public string $externalId,        // 'movie:603'
        public string $categorySlug,      // 'peliculas'
        public string $name,              // en español (o el original si no hay)
        public ?string $originalTitle,
        public ?string $description,       // ya en español; null si no hay
        public ?string $descriptionSource, // 'tmdb' | 'wikipedia_es' | 'wikidata' | null
        public ?string $descriptionUrl,
        public ?string $releasedAt,        // 'Y-m-d'
        public array $attributes,        // claves según attribute_schema
        public float $popularity,
        public ?string $wikidataId,
        public ?string $sourceUrl,         // ficha pública en la fuente
        public array $images,            // [['url', 'role' => 'poster'|'banner', 'license', 'author', 'source_url']]
    ) {}

    /** Copia del DTO con otra descripción (DescriptionResolver, F1-04); el resto de campos no cambia. */
    public function withDescription(?string $description, ?string $descriptionSource, ?string $descriptionUrl): self
    {
        return new self(
            provider: $this->provider,
            externalId: $this->externalId,
            categorySlug: $this->categorySlug,
            name: $this->name,
            originalTitle: $this->originalTitle,
            description: $description,
            descriptionSource: $descriptionSource,
            descriptionUrl: $descriptionUrl,
            releasedAt: $this->releasedAt,
            attributes: $this->attributes,
            popularity: $this->popularity,
            wikidataId: $this->wikidataId,
            sourceUrl: $this->sourceUrl,
            images: $this->images,
        );
    }
}
