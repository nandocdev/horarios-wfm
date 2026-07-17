<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CuicBackfillReport extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{total_records: int, by_type: array<string, int>, errors: string[]}  $stats
     */
    public function __construct(
        public string $date,
        public array $stats
    ) {}

    public function build(): self
    {
        return $this->subject("Reporte de Backfill CUIC - {$this->date}")
            ->view('connect::emails.backfill-report');
    }
}
