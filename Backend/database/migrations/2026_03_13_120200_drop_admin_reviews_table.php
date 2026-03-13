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
        Schema::dropIfExists('admin_reviews');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('admin_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignUuid('admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('action', ['approved', 'rejected']);
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->useCurrent();

            $table->timestamps();
        });
    }
};

