<?php

namespace App\Actions;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Crea un reporte de contenido (F3-04, DSA). Un usuario no puede tener dos reportes pendientes del mismo contenido.
 */
class CreateReportAction
{
    public const DUPLICATE_MESSAGE = 'Ya has reportado este contenido y está pendiente de revisión.';

    /**
     * @throws ConflictHttpException si el usuario ya tiene un reporte pendiente de ese contenido.
     */
    public function execute(User $reporter, Model $reportable, string $reason, ?string $details): Report
    {
        $alreadyPending = Report::query()
            ->pending()
            ->where('reporter_id', $reporter->id)
            ->where('reportable_type', $reportable->getMorphClass())
            ->where('reportable_id', $reportable->getKey())
            ->exists();

        if ($alreadyPending) {
            throw new ConflictHttpException(self::DUPLICATE_MESSAGE);
        }

        try {
            return Report::create([
                'reporter_id' => $reporter->id,
                'reportable_type' => $reportable->getMorphClass(),
                'reportable_id' => $reportable->getKey(),
                'reason' => $reason,
                'details' => $details,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Carrera: otra petición creó el mismo reporte entre la comprobación y el insert (índice parcial único).
            throw new ConflictHttpException(self::DUPLICATE_MESSAGE);
        }
    }
}
