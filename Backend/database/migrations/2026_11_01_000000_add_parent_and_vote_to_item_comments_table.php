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
        Schema::table('item_comments', function (Blueprint $table) {
            // Respuesta (F3-02): un solo nivel, validado en StoreItemCommentRequest. Al borrar de verdad el padre se borran sus respuestas.
            $table->foreignUuid('parent_id')->nullable()->constrained('item_comments')->cascadeOnDelete();
            // Reseña (F3-02): el voto "vote" del autor en el ítem. Si se borra el voto, queda como comentario normal.
            $table->foreignUuid('vote_id')->nullable()->constrained('votes')->nullOnDelete();

            $table->index(['parent_id', 'created_at']);
            $table->index('vote_id');
        });

        // Como mucho una reseña viva por usuario e ítem (protege de dos peticiones simultáneas).
        // Las reseñas borradas (borrado suave) no cuentan; las ocultas sí.
        DB::statement('CREATE UNIQUE INDEX item_comments_review_unique ON item_comments (item_id, user_id) WHERE vote_id IS NOT NULL AND deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS item_comments_review_unique');

        Schema::table('item_comments', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'created_at']);
            $table->dropIndex(['vote_id']);
            $table->dropConstrainedForeignId('vote_id');
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
