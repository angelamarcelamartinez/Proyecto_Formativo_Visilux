<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de "recuperar contraseña" en español.
 */
class RestablecerContrasena extends ResetPassword
{
    public function toMail($notifiable)
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $minutos = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablece tu contraseña | VisiOptica')
            ->greeting('¡Hola, '.($notifiable->nombres ?? '').'!')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line("Este enlace vence en {$minutos} minutos.")
            ->line('Si no solicitaste este cambio, puedes ignorar este correo; tu contraseña seguirá igual.')
            ->salutation('Equipo VisiOptica');
    }
}
