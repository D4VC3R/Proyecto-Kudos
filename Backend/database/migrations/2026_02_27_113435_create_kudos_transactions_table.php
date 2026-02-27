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
        Schema::create('kudos_transactions', function (Blueprint $table) {
            $table->uuid()->primary();
						$table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
						$table->integer('kudos_amount');
						$table->enum('reason', ['item_voted', 'item_accepted']);
						$table->uuidMorphs('reference');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kudos_transactions');
    }
};
