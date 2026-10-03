<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Correo de verificación de email enviado a través de la cola.
 * Mantiene el comportamiento de VerifyEmail (incluida la URL personalizada de AppServiceProvider,
 * que se comparte por ser una propiedad estática), pero la petición de registro no espera al servidor SMTP.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
