<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioControlMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  object  $paciente  documento, nombres, apellido, correo, ultima_cita (Carbon), fecha_aniversario (Carbon)
     */
    public function __construct(public object $paciente)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Es momento de tu cita de control · Óptica Visilux',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio_control',
        );
    }
}
