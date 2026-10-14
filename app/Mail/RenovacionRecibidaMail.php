<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\Licencia;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmación de que se recibió el pago de una renovación. Va a la óptica y a su administrador.
 * Las credenciales no cambian: el superadmin solo tiene que aceptar la renovación.
 */
class RenovacionRecibidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Empresa $empresa, public Licencia $licencia)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Recibimos tu pago: renovación de {$this->empresa->nombre}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.renovacion_recibida');
    }
}
