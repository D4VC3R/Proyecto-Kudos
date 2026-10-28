<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('item_id')->constrained('items')->cascadeOnDelete();
            // Proveedor: tmdb, igdb, rawg, wikidata o wikipedia_es.
            $table->string('provider', 32);
            // TMDB lleva prefijo (movie:603 / tv:1399) porque sus IDs se solapan.
            $table->string('external_id', 64);
            $table->string('url')->nullable();
            // El proveedor que manda; uno por ítem (lo garantiza el importador, F1-04).
            $table->boolean('is_primary')->default(false);
            $table->timestampTz('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_id']);
            $table->index('item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_sources');
    }
};
