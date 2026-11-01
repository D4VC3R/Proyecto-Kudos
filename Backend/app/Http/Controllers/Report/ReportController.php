<?php

namespace App\Http\Controllers\Report;

use App\Actions\CreateReportAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreReportRequest;
use App\Http\Resources\ReportResource;
use Illuminate\Http\JsonResponse;

/**
 * Reportes de contenido de los usuarios (F3-04, DSA).
 */
class ReportController extends Controller
{
    /**
     * POST /api/reports: reporta un comentario, una lista pública o un perfil.
     * 409 si el usuario ya tiene un reporte pendiente de ese contenido.
     */
    public function store(StoreReportRequest $request, CreateReportAction $action): JsonResponse
    {
        $validated = $request->validated();

        $report = $action->execute(
            $request->user(),
            $request->reportable(),
            $validated['reason'],
            $validated['details'] ?? null,
        );

        return $this->respondMutation(
            'Gracias. Revisaremos tu reporte lo antes posible.',
            new ReportResource($report),
            status: 201,
        );
    }
}
