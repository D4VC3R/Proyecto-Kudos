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
}
