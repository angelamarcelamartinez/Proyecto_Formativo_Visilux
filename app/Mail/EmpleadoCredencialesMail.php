<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Credenciales de un empleado que el administrador de la óptica acaba de crear
 * (o a quien le restableció la contraseña). La contraseña es temporal.
 */
class EmpleadoCredencialesMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Empresa $empresa,
        public Usuario $empleado,
        public string $rol,
        public string $password,
        public bool $restablecida = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->restablecida ? 'Nueva contraseña temporal' : 'Tu cuenta') . " en {$this->empresa->nombre}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.empleado_credenciales');
    }
}
