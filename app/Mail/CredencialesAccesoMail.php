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
 * Se envía al correo de la óptica cuando el superadmin aprueba su pago. Trae el usuario
 * (correo del administrador) y una contraseña temporal que debe cambiar al entrar.
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
