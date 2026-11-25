<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de restablecimiento de contraseña enviado a través de la cola.
 * Mantiene el comportamiento de ResetPassword (incluida la URL personalizada de AppServiceProvider,
 * que se comparte por ser una propiedad estática), pero la petición no espera al servidor SMTP.
 */
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Texto del correo en español. La URL la sigue construyendo ResetPassword::toMail() con el callback
     * de AppServiceProvider; el saludo y la despedida salen de lang/es.json.
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Restablece tu contraseña')
            ->line('Has recibido este correo porque alguien ha pedido restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line('El enlace caduca en ' . config('auth.passwords.' . config('auth.defaults.passwords') . '.expire') . ' minutos.')
            ->line('Si no lo has pedido tú, no tienes que hacer nada.');
    }
}
