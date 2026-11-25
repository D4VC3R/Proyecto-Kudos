<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Activa `videojuegos` con el attribute_schema de IGDB (F4-01). Si la categoría no existe, no hace nada.
     */
    public function up(): void
    {
        DB::table('categories')->where('slug', 'videojuegos')->update([
            'is_active' => true,
            // Esquema escrito aquí, no leído del proveedor: la migración aplica siempre el mismo aunque la constante cambie o la clase se renombre.
            'attribute_schema' => json_encode([
                ['key' => 'year', 'label' => 'Año', 'type' => 'integer', 'filterable' => true, 'show_in_card' => true],
                ['key' => 'genres', 'label' => 'Géneros', 'type' => 'tags', 'filterable' => true, 'show_in_card' => true],
                ['key' => 'platforms', 'label' => 'Plataformas', 'type' => 'tags', 'filterable' => true, 'show_in_card' => false],
                ['key' => 'developers', 'label' => 'Desarrollo', 'type' => 'tags', 'filterable' => false, 'show_in_card' => false],
            ]),
            'updated_at' => now(),
        ]);
    }

    /**
     * Vuelve al estado de la F1-10: inactiva y sin attribute_schema.
     */
    public function down(): void
    {
        DB::table('categories')->where('slug', 'videojuegos')->update([
            'is_active' => false,
            'attribute_schema' => '[]',
            'updated_at' => now(),
        ]);
    }
};
