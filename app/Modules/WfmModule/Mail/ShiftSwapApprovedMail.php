<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Mail;

use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShiftSwapApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShiftSwapRequest $swap,
        public Employee $approver,
        public Employee $recipient_user // El usuario al que va dirigido este correo específico
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de Cambio de Turno - ' . $this->swap->requested_date->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.wfm.shift-swap-approved',
        );
    }
}
