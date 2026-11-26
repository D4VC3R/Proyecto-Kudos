<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de verificación de email enviado a través de la cola.
 * Mantiene el comportamiento de VerifyEmail (incluida la URL personalizada de AppServiceProvider,
 * que se comparte por ser una propiedad estática), pero la petición de registro no espera al servidor SMTP.
 */
class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Texto del correo en español. La URL la sigue construyendo VerifyEmail::toMail() con el callback
     * de AppServiceProvider; el saludo y la despedida salen de lang/es.json.
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirma tu correo electrónico')
            ->line('Pulsa el botón para confirmar tu dirección de correo.')
            ->action('Confirmar correo', $url)
            ->line('Si no has creado una cuenta en '.config('app.name').', no tienes que hacer nada.');
    }
}
