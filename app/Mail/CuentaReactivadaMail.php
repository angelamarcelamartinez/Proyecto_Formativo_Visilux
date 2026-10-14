<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa al usuario que su cuenta fue activada de nuevo y ya puede iniciar sesión.
 */
class CuentaReactivadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Usuario $usuario)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu cuenta fue activada de nuevo | VisiOptica');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.cuenta_reactivada');
    }
}
