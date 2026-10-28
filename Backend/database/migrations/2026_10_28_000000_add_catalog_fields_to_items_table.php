<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Campos del catálogo (F1-01). Ver docs/backend/base-de-datos.md §3 (Fase 1).
            $table->string('slug')->nullable()->unique();
            $table->string('original_title')->nullable();
            $table->date('released_at')->nullable();
            $table->jsonb('attributes')->default('{}');
            $table->float('popularity')->default(0);
            $table->string('wikidata_id', 16)->nullable()->unique();
            $table->string('description_source', 32)->nullable();
            $table->string('description_url')->nullable();
            $table->timestampTz('last_synced_at')->nullable();

            // "Sin descripción" es válido: baja quality_score y nunca se rellena con plantillas.
            $table->text('description')->nullable()->change();

            $table->index(['category_id', 'status', 'released_at']);
        });

        DB::statement('CREATE INDEX items_original_title_trgm_idx ON items USING GIN (original_title gin_trgm_ops);');
        DB::statement('CREATE INDEX items_attributes_gin_idx ON items USING GIN (attributes);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS items_attributes_gin_idx;');
        DB::statement('DROP INDEX IF EXISTS items_original_title_trgm_idx;');

        // Para volver a NOT NULL, las descripciones vacías pasan a cadena vacía.
        DB::table('items')->whereNull('description')->update(['description' => '']);

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'status', 'released_at']);
            $table->dropUnique(['slug']);
            $table->dropUnique(['wikidata_id']);
            $table->dropColumn([
                'slug', 'original_title', 'released_at', 'attributes', 'popularity',
                'wikidata_id', 'description_source', 'description_url', 'last_synced_at',
            ]);
            $table->text('description')->nullable(false)->change();
        });
    }
};
