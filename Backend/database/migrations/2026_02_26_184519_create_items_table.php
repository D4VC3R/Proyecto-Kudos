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
		    $table->json('images')->nullable();
		    $table->enum('status', ['active', 'inactive'])->default('active');

		    $table->float('vote_avg')->default(0.0);
		    $table->integer('vote_count')->default(0);

		    $table->foreignUuid('creator_id')->nullable()->constrained('users')->nullOnDelete();
		    $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();

		    $table->softDeletes();
		    $table->timestamps();

		    $table->index(['status', 'vote_avg', 'vote_count']);
		    $table->index(['category_id', 'status']);
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
