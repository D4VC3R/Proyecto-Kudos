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
        Schema::create('admin_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
						$table->foreignUuid('admin_id')->constrained('users')->nullOnDelete();
						$table->foreignUuid('item_id')->constrained('items')->nullOnDelete();
						$table->enum('final_state',['accepted', 'rejected']);
						$table->string('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_reviews');
    }
};
