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
        Schema::table('users', function (Blueprint $table) {
            // Nombre público del perfil (/u/{username}, F3-03): 3–30 caracteres [a-z0-9_], validado en UpdateProfileRequest.
            // Nullable: nadie lo tiene hasta que lo elige en su perfil. Se guarda siempre en minúsculas.
            $table->string('username', 30)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
