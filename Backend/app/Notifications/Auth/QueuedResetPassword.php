<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Correo de restablecimiento de contraseña enviado a través de la cola.
 * Mantiene el comportamiento de ResetPassword (incluida la URL personalizada de AppServiceProvider,
 * que se comparte por ser una propiedad estática), pero la petición no espera al servidor SMTP.
 */
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;
}
