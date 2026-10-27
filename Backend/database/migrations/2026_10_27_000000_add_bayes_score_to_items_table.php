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
            // Puntuación bayesiana para el ranking (F0-04). La calcula ItemStatsService.
            $table->float('bayes_score')->default(0);
            $table->index(['category_id', 'status', 'bayes_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'status', 'bayes_score']);
            $table->dropColumn('bayes_score');
        });
    }
};
