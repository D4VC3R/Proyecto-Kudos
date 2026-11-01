<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResolveReportAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAdminReportsRequest;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Http\Resources\Admin\AdminReportResource;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

/**
 * Cola de revisión de reportes de contenido por parte del administrador (F3-04).
 */
class AdminReportController extends Controller
{
    public function __construct(
        private readonly ResolveReportAction $resolveReport,
    ) {}

    /**
     * GET /api/admin/reports: los reportes, los más recientes primero, filtrables por estado y tipo.
     * `meta.pending_count` es el total de reportes pendientes (independiente de los filtros).
     */
    public function index(ListAdminReportsRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $reports = Report::query()
            ->withAdminRelations()
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['reportable_type']), fn ($query) => $query->where('reportable_type', Report::REPORTABLE_TYPES[$filters['reportable_type']]))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20));

        return $this->respondList(
            data: AdminReportResource::collection($reports),
            meta: [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'pending_count' => Report::pending()->count(),
            ],
        );
    }

    /**
     * POST /api/admin/reports/{report}/resolve: descarta el reporte u oculta o borra el contenido.
     */
    public function resolve(ResolveReportRequest $request, Report $report): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->resolveReport->execute($report, $request->user(), $validated['action'], $validated['note'] ?? null);

        return $this->respondMutation(
            message: $validated['action'] === Report::ACTION_DISMISS ? 'Reporte descartado.' : 'Reporte resuelto.',
            data: new AdminReportResource($result['report']),
            meta: [
                'content_action' => $result['content_action'],
                'closed_reports' => $result['closed_reports'],
            ],
        );
    }
}
