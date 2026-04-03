<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_field_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('key', 120);
            $table->string('label', 255);
            $table->string('type', 40);
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->json('rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['category_id', 'key'], 'category_field_definitions_unique_key');
            $table->index(['category_id', 'is_active', 'sort_order'], 'category_field_definitions_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_field_definitions');
    }
};

