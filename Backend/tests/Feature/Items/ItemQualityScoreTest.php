<?php

namespace Tests\Feature\Items;

use App\Models\Item;
use App\Services\ItemQualityScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesItems;
use Tests\TestCase;

class ItemQualityScoreTest extends TestCase
{
    use RefreshDatabase, CreatesItems;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function poorItem(array $attrs = []): Item
    {
        return $this->createItem($this->createCategory(), array_merge([
            'name' => 'Ficha pobre',
            'images' => [],
            'description' => 'Item importado.',
        ], $attrs));
    }

    private function image(): array
    {
        return [[
            'disk' => 'public', 'alt' => null, 'order' => 0,
            'variants' => ['thumb' => 'items/tests/a-thumb.webp', 'banner' => 'items/tests/a-banner.webp'],
        ]];
    }

    public function test_un_item_sin_imagen_y_con_descripcion_de_plantilla_puntua_menos_de_40(): void
    {
        $item = $this->poorItem();

        $this->assertLessThan(40, $item->fresh()->quality_score);
        $this->assertSame(0, $item->fresh()->quality_score);
    }

    public function test_una_ficha_con_imagen_y_descripcion_larga_puntua_80(): void
    {
        $item = $this->createItem($this->createCategory());

        $this->assertSame(80, $item->fresh()->quality_score);
    }

    public function test_una_descripcion_larga_con_patron_de_plantilla_no_suma(): void
    {
        $item = $this->poorItem([
            'description' => 'Artista musical destacado IMPORTADO DESDE Wikidata (España) con datos extra.',
        ]);

        $this->assertGreaterThanOrEqual(60, mb_strlen($item->description));
        $this->assertSame(0, $item->fresh()->quality_score);
    }

    public function test_una_descripcion_de_menos_de_60_caracteres_no_suma(): void
    {
        $item = $this->createItem($this->createCategory(), ['description' => str_repeat('a', 59)]);

        $this->assertSame(40, $item->fresh()->quality_score);
    }

    public function test_la_fecha_de_estreno_o_los_atributos_suman_20(): void
    {
        $scorer = app(ItemQualityScorer::class);

        $withDate = new Item(['description' => 'corta', 'images' => []]);
        $withDate->setAttribute('released_at', '2020-01-01');
        $this->assertSame(20, $scorer->score($withDate));

        $withAttrs = new Item(['description' => 'corta', 'images' => []]);
        $withAttrs->setAttribute('attributes', ['year' => 1999]);
        $this->assertSame(20, $scorer->score($withAttrs));

        $emptyAttrs = new Item(['description' => 'corta', 'images' => []]);
        $emptyAttrs->setAttribute('attributes', []);
        $this->assertSame(0, $scorer->score($emptyAttrs));
    }

    public function test_el_hook_recalcula_al_cambiar_imagenes_o_descripcion(): void
    {
        $item = $this->poorItem();
        $this->assertSame(0, $item->fresh()->quality_score);

        $item->update(['images' => $this->image()]);
        $this->assertSame(40, $item->fresh()->quality_score);

        $item->update(['description' => 'Una descripción real, escrita a mano y con longitud más que suficiente.']);
        $this->assertSame(80, $item->fresh()->quality_score);
    }

    public function test_el_hook_no_recalcula_si_no_cambian_imagenes_ni_descripcion(): void
    {
        $item = $this->createItem($this->createCategory());
        DB::table('items')->where('id', $item->id)->update(['quality_score' => 55]);

        $item->fresh()->update(['vote_avg' => 5]);

        $this->assertSame(55, $item->fresh()->quality_score);
    }

    public function test_los_patrones_de_plantilla_vienen_de_config(): void
    {
        config(['catalog.template_description_patterns' => ['texto vetado']]);

        $item = $this->poorItem([
            'description' => 'Esta descripción es bastante larga pero contiene el texto vetado en medio de la frase.',
        ]);

        $this->assertSame(0, $item->fresh()->quality_score);
    }
}
