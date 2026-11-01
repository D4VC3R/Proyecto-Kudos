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
        Schema::table('lists', function (Blueprint $table) {
            // F3-01: fecha en que la moderación ocultó la lista (reporte resuelto con hide). Con valor,
            // el dueño no puede volver a hacerla pública ni no listada.
            $table->timestamp('hidden_by_moderation_at')->nullable()->after('likes_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lists', function (Blueprint $table) {
            $table->dropColumn('hidden_by_moderation_at');
        });
    }
};
