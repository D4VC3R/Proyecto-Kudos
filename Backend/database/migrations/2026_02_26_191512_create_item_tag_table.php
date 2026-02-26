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
        Schema::create('item_tag', function (Blueprint $table) {
            // Por si acaso, de momento en nullOnDelete pero hay que cambiarlo a CascadeOnDelete.
            $table->foreignUuid('item_id')->constrained('items')->nullOnDelete();
            $table->foreignUuid('tag_id')->constrained('tags')->nullOnDelete();
            // Clave primaria compuesta por los ids de las tablas relacionadas.
            $table->primary(['item_id', 'tag_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_tag');
    }
};
