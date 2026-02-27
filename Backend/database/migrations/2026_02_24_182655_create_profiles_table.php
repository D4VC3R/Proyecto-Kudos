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
    Schema::create('profiles', function (Blueprint $table) {
        $table->uuid('id')->primary();
        // Clave foránea al usuario (Importante: foreignUuid)
        $table->foreignUuid('user_id')->unique()->constrained()->onDelete('cascade');

        $table->string('avatar')->nullable();
        $table->text('biography')->nullable();
        $table->json('social_links')->nullable(); // Para Twitter, Insta, etc.
        $table->string('city')->nullable();
				$table->date('birthdate')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
