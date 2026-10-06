<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Se envía al administrador de una óptica nueva cuando el superadmin aprueba su pago.
 */
class CredencialesAccesoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Empresa $empresa,
        public Usuario $admin,
        public string $password,
        public Licencia $licencia,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tu cuenta de {$this->empresa->nombre} en VisiOptica está lista",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.credenciales_acceso');
    }
}
