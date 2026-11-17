<?php

// Correo que avisa que el plan quedó ACTIVO (renovación aprobada o plan asignado por el superadmin).
namespace App\Mail;

use App\Models\Empresa;
use App\Models\Licencia;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirma a una óptica que su plan quedó activo (renovación o plan asignado por el superadmin).
 */
// Se envía desde Licencias::notificarActivacion().
class RenovacionAprobadaMail extends Mailable
{
    use Queueable, SerializesModels;

    // Recibe la óptica y la licencia; quedan disponibles en la vista.
    public function __construct(public Empresa $empresa, public Licencia $licencia)
    {
    }

    // Asunto: nombre del plan y de la óptica.
    public function envelope(): Envelope
    {
        return new Envelope(subject: "Plan {$this->licencia->plan->nombre} activo para {$this->empresa->nombre}");
    }

    // Vista: resources/views/emails/renovacion_aprobada.blade.php
    public function content(): Content
    {
        return new Content(view: 'emails.renovacion_aprobada');
    }
}
