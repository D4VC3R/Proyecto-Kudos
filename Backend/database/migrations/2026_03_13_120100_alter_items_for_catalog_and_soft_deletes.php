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
        Schema::table('items', function (Blueprint $table) {
            $table->json('images')->nullable()->after('description');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('images');
            $table->softDeletes();
        });

        // Migrate legacy single image into the new JSON images field.
        DB::table('items')
            ->whereNotNull('image')
            ->update([
                'images' => DB::raw("JSON_ARRAY(JSON_OBJECT('path', image, 'disk', 'public', 'alt', NULL, 'order', 0))"),
            ]);

        // Legacy state to new publication status mapping.
        DB::table('items')
            ->whereIn('state', ['pending', 'in_progress', 'accepted'])
            ->update(['status' => 'active']);

        DB::table('items')
            ->where('state', 'rejected')
            ->update(['status' => 'inactive']);

        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locked_by_admin_id');
            $table->dropColumn(['locked_at', 'state', 'image']);

            $table->index(['status', 'vote_avg', 'vote_count']);
            $table->index(['category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
            $table->enum('state', ['pending', 'in_progress', 'accepted', 'rejected'])->default('pending')->after('image');
            $table->timestamp('locked_at')->nullable()->after('vote_count');
            $table->foreignUuid('locked_by_admin_id')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
        });

        DB::table('items')
            ->where('status', 'active')
            ->update(['state' => 'accepted']);

        DB::table('items')
            ->where('status', 'inactive')
            ->update(['state' => 'rejected']);

        // Restore first image path back to legacy image field.
        DB::table('items')
            ->whereNotNull('images')
            ->update([
                'image' => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(images, '$[0].path'))"),
            ]);

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['status', 'vote_avg', 'vote_count']);
            $table->dropIndex(['category_id', 'status']);

            $table->dropSoftDeletes();
            $table->dropColumn(['status', 'images']);
        });
    }
};

