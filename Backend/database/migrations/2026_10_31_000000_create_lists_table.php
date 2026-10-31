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
        Schema::create('lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            // Privada por defecto (RGPD, legal/cumplimiento §4). Las editoriales se crean siempre públicas.
            $table->enum('visibility', ['public', 'unlisted', 'private'])->default('private');
            $table->boolean('is_ranked')->default(false);
            $table->boolean('is_editorial')->default(false);
            $table->foreignUuid('cover_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->integer('items_count')->default(0);
            // Lo usarán los "me gusta" de la F3.
            $table->integer('likes_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index(['is_editorial', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lists');
    }
};
