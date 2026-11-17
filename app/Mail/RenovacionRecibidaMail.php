<?php

// Correo que confirma que se RECIBIÓ el pago de la renovación (aún falta que el superadmin la apruebe).
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
// Se envía desde Licencias::notificarPagoRecibido().
class RenovacionRecibidaMail extends Mailable
{
    use Queueable, SerializesModels;

    // Recibe la óptica y la licencia; quedan disponibles en la vista.
    public function __construct(public Empresa $empresa, public Licencia $licencia)
    {
    }

    // Asunto: "Recibimos tu pago..." con el nombre de la óptica.
    public function envelope(): Envelope
    {
        return new Envelope(subject: "Recibimos tu pago: renovación de {$this->empresa->nombre}");
    }

    // Vista: resources/views/emails/renovacion_recibida.blade.php
    public function content(): Content
    {
        return new Content(view: 'emails.renovacion_recibida');
    }
}
