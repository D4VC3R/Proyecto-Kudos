<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Datos públicos de las páginas legales (F3-07).
 */
class LegalInfoController extends Controller
{
    /**
     * GET /api/legal: público. Devuelve el correo de contacto legal (LEGAL_CONTACT_EMAIL), el mismo que reciben
     * los autores como vía de reclamación (F3-04). Null si no está configurado.
     */
    public function __invoke(): JsonResponse
    {
        return $this->respondData([
            'contact_email' => config('kudos.legal.contact_email'),
        ]);
    }
}
