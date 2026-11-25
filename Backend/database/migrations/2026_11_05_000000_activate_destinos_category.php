<?php

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
            // Esquema escrito aquí, no leído del proveedor: la migración aplica siempre el mismo aunque la constante cambie o la clase se renombre.
            'attribute_schema' => json_encode([
                ['key' => 'kind', 'label' => 'Tipo', 'type' => 'string', 'filterable' => true, 'show_in_card' => true],
                ['key' => 'country', 'label' => 'País', 'type' => 'string', 'filterable' => true, 'show_in_card' => true],
                ['key' => 'population', 'label' => 'Población', 'type' => 'integer', 'filterable' => false, 'show_in_card' => false],
                ['key' => 'coordinates', 'label' => 'Coordenadas', 'type' => 'coordinates', 'filterable' => false, 'show_in_card' => false],
            ]),
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
