<?php

namespace Tests\Unit\Catalog\Data;

use App\Catalog\Data\CatalogItemData;
use Error;
use Tests\TestCase;

class CatalogItemDataTest extends TestCase
{
    public function test_el_dto_es_inmutable(): void
    {
        $data = new CatalogItemData(
            provider: 'tmdb',
            externalId: 'movie:603',
            categorySlug: 'peliculas',
            name: 'Matrix',
            originalTitle: 'The Matrix',
            description: null,
            descriptionSource: null,
            descriptionUrl: null,
            releasedAt: '1999-03-31',
            attributes: [],
            popularity: 80.5,
            wikidataId: 'Q83495',
            sourceUrl: null,
            images: [],
        );

        $this->assertSame('Matrix', $data->name);
        $this->assertSame('movie:603', $data->externalId);

        $this->expectException(Error::class);
        $data->name = 'Otro';
    }

    public function test_with_description_devuelve_una_copia_con_otra_descripcion(): void
    {
        $data = new CatalogItemData(
            provider: 'tmdb',
            externalId: 'movie:603',
            categorySlug: 'peliculas',
            name: 'Matrix',
            originalTitle: 'The Matrix',
            description: null,
            descriptionSource: null,
            descriptionUrl: null,
            releasedAt: '1999-03-31',
            attributes: ['year' => 1999],
            popularity: 80.5,
            wikidataId: 'Q83495',
            sourceUrl: 'https://www.themoviedb.org/movie/603',
            images: [['url' => 'https://image.tmdb.org/t/p/w780/a.jpg', 'role' => 'poster', 'license' => null, 'author' => null, 'source_url' => null]],
        );

        $copy = $data->withDescription('Texto.', 'wikipedia_es', 'https://es.wikipedia.org/wiki/Matrix');

        $this->assertNull($data->description);
        $this->assertNull($data->descriptionSource);
        $this->assertSame('Texto.', $copy->description);
        $this->assertSame('wikipedia_es', $copy->descriptionSource);
        $this->assertSame('https://es.wikipedia.org/wiki/Matrix', $copy->descriptionUrl);
        $this->assertNotSame($data, $copy);

        foreach (['provider', 'externalId', 'categorySlug', 'name', 'originalTitle', 'releasedAt', 'attributes', 'popularity', 'wikidataId', 'sourceUrl', 'images'] as $field) {
            $this->assertSame($data->{$field}, $copy->{$field}, $field);
        }
    }
}
