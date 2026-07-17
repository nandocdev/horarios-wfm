<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ImportErrorNotification extends Mailable {
    use Queueable, SerializesModels;

    public function __construct(
        public string $fileName,
        public string $errorMessage,
        public string $stackTrace = ''
    ) {
    }

    public function envelope(): Envelope {
        return new Envelope(
            subject: "⚠️ Error de Importación UCCX: {$this->fileName}",
        );
    }

    public function content(): Content {
        return new Content(
            markdown: 'connect::emails.import-error',
        );
    }
}
