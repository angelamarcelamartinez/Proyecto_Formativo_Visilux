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
 * Confirma a una óptica que su plan quedó activo (renovación o plan asignado por el superadmin).
 */
class RenovacionAprobadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Empresa $empresa, public Licencia $licencia)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Plan {$this->licencia->plan->nombre} activo para {$this->empresa->nombre}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.renovacion_aprobada');
    }
}
