<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Amplía el CHECK de votes.type con want y not_interested (F2-01) y añade el índice (user_id, type).
 * $table->enum() en PostgreSQL crea un varchar + CHECK (votes_type_check): hay que borrarlo y recrearlo con SQL.
 * skip se mantiene como valor heredado; la API lo normaliza a not_interested.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE votes DROP CONSTRAINT votes_type_check');
        DB::statement("ALTER TABLE votes ADD CONSTRAINT votes_type_check CHECK (type IN ('vote', 'skip', 'want', 'not_interested'))");

        Schema::table('votes', function (Blueprint $table) {
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Convierte want y not_interested en skip (sin nota) y restaura la restricción original.
     */
    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'type']);
        });

        DB::table('votes')
            ->whereIn('type', ['want', 'not_interested'])
            ->update(['type' => 'skip', 'score' => null]);

        DB::statement('ALTER TABLE votes DROP CONSTRAINT votes_type_check');
        DB::statement("ALTER TABLE votes ADD CONSTRAINT votes_type_check CHECK (type IN ('vote', 'skip'))");
    }
};
