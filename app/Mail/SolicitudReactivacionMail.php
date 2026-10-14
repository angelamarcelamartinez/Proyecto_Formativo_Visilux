<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Le llega al administrador de la óptica cuando un usuario inactivado
 * (por intentos fallidos) pide que le activen la cuenta.
 */
class SolicitudReactivacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Usuario $usuario)
    {
    }

    public function envelope(): Envelope
    {
        $nombre = trim($this->usuario->nombres . ' ' . $this->usuario->apellido);

        return new Envelope(
            subject: "Solicitud de activación de cuenta: {$nombre}",
            // "Responder" en el correo le escribe directamente al usuario.
            replyTo: [new Address($this->usuario->email, $nombre)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.solicitud_reactivacion');
    }
}
