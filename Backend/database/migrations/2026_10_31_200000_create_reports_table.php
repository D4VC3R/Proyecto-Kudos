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
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Si se borra la cuenta del denunciante, el reporte se conserva (registro DSA).
            $table->foreignUuid('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            // reportable_type guarda el FQCN (App\Models\ItemComment, UserList o User); crea también el índice (type, id).
            $table->uuidMorphs('reportable');
            $table->enum('reason', ['spam', 'odio', 'acoso', 'ilegal', 'spoiler', 'otro']);
            $table->text('details')->nullable();
            $table->enum('status', ['pending', 'resolved', 'dismissed'])->default('pending');
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Un usuario no puede tener dos reportes pendientes del mismo contenido (protege de carreras).
        DB::statement("CREATE UNIQUE INDEX reports_pending_unique ON reports (reporter_id, reportable_type, reportable_id) WHERE status = 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // El índice parcial se borra junto con la tabla.
        Schema::dropIfExists('reports');
    }
};
