<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reporte de contenido (F3-04) tal y como lo ve quien lo envía.
 *
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reportable_type' => $this->reportableAlias(),
            'reportable_id' => $this->reportable_id,
            'reason' => $this->reason,
            'reason_label' => $this->reasonLabel(),
            'details' => $this->details,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
