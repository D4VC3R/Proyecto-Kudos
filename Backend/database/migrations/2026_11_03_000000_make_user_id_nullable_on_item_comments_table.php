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
            // F3-08: al borrar una cuenta, los comentarios con respuestas ajenas se anonimizan (user_id = null).
            // Solo cambia la nulabilidad: la FK item_comments_user_id_foreign (cascadeOnDelete) se conserva.
            $table->uuid('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Los comentarios anonimizados no tienen autor al que volver: se borran (y sus respuestas, por la FK parent_id).
        DB::table('item_comments')->whereNull('user_id')->delete();

        Schema::table('item_comments', function (Blueprint $table) {
            $table->uuid('user_id')->nullable(false)->change();
        });
    }
};
