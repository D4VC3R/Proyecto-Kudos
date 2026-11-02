<?php

use App\Catalog\Providers\WikidataPlaceProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Activa `destinos` con el attribute_schema de Wikidata (F4-02). Si la categoría no existe, no hace nada.
     */
    public function up(): void
    {
        DB::table('categories')->where('slug', 'destinos')->update([
            'is_active' => true,
            'attribute_schema' => json_encode(WikidataPlaceProvider::ATTRIBUTE_SCHEMA),
            'updated_at' => now(),
        ]);
    }

    /**
     * Vuelve al estado de la F1-10: inactiva y sin attribute_schema.
     */
    public function down(): void
    {
        DB::table('categories')->where('slug', 'destinos')->update([
            'is_active' => false,
            'attribute_schema' => '[]',
            'updated_at' => now(),
        ]);
    }
};
