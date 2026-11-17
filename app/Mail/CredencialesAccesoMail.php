<?php

// Correo (Mailable): clase que describe un email. Se envía con Mail::to(...)->send(new CredencialesAccesoMail(...)).
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
// Lleva a la óptica y al administrador sus credenciales de acceso (usuario = correo del admin, contraseña temporal).
class CredencialesAccesoMail extends Mailable
{
    // Traits de Laravel: permiten poner el correo en cola y serializar los modelos.
    use Queueable, SerializesModels;

    // Los datos que recibe quedan disponibles en la vista del correo como $empresa, $admin, $password y $licencia.
    public function __construct(
        public Empresa $empresa,
        public Usuario $admin,
        public string $password,
        public Licencia $licencia,
    ) {
    }

    // Define el asunto del correo.
    public function envelope(): Envelope
    {
        return new Envelope(
            // Asunto con el nombre de la óptica.
            subject: "Tu cuenta de {$this->empresa->nombre} en VisiOptica está lista",
        );
    }

    // Define qué vista Blade es el cuerpo del correo.
    public function content(): Content
    {
        // resources/views/emails/credenciales_acceso.blade.php
        return new Content(view: 'emails.credenciales_acceso');
    }
}
