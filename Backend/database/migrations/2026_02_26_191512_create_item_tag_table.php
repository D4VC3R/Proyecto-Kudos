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

            $table->foreignUuid('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignUuid('tag_id')->constrained('tags')->cascadeOnDelete();
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
