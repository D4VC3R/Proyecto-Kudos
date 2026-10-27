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
        Schema::table('items', function (Blueprint $table) {
            // Calidad de la ficha (F0-05), 0–100. La calcula ItemQualityScorer.
            $table->smallInteger('quality_score')->default(0);
            $table->index(['category_id', 'status', 'quality_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'status', 'quality_score']);
            $table->dropColumn('quality_score');
        });
    }
};
