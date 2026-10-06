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
 * "Tu licencia vence en X días": se envía a los 30 y a los 7 días del vencimiento.
 */
class AvisoVencimientoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Empresa $empresa, public Licencia $licencia, public int $dias)
    {
    }

    public function envelope(): Envelope
    {
        $cuando = $this->dias <= 0 ? 'vence hoy' : "vence en {$this->dias} " . ($this->dias === 1 ? 'día' : 'días');

        return new Envelope(subject: "La licencia de {$this->empresa->nombre} {$cuando}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.aviso_vencimiento');
    }
}
