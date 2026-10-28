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
        Schema::table('categories', function (Blueprint $table) {
            // Formato en docs/backend/base-de-datos.md §3 (Fase 1).
            $table->jsonb('attribute_schema')->default('[]');
            // Las inactivas no salen en la portada ni en la API pública (F1-06).
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);
            // Icono lucide (clapperboard, gamepad-2, map-pin) para los placeholders.
            $table->string('icon', 32)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['attribute_schema', 'is_active', 'sort_order', 'icon']);
        });
    }
};
