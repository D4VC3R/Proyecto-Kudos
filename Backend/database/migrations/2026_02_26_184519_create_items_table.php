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
        Schema::create('items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description');
            $table->string('image')->nullable();

            $table->enum('state', ['pending', 'active', 'inactive', 'accepted', 'rejected'])->default('pending');

            $table->float('vote_avg')->default(0.0);
            $table->integer('vote_count')->default(0);

            $table->timestamp('locked_at')->nullable();
            $table->foreignUuid('locked_by_admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignUuid('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
