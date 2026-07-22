<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use App\Reports\BaseReport;
use Barryvdh\DomPDF\PDF as DomPDFInstance;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

final class GeneratePdfReportAction
{
    public function execute(array $data, string $view, string $title, string $orientation = 'portrait'): DomPDFInstance
    {
        $report = new class($data, $view, $title, $orientation) extends BaseReport
        {
            public function __construct(
                private readonly array $data,
                private readonly string $view,
                string $title,
                string $orientation,
            ) {
                parent::__construct();
                $this->title($title);
                $this->orientation($orientation);
            }

            public function data(): array
            {
                return $this->data;
            }

            public function view(): View
            {
                return ViewFacade::make($this->view, $this->data);
            }
        };

        $filename = sprintf('%s_%s.pdf', str_replace(' ', '_', $title), now()->format('Ymd_His'));

        return $report->download($filename);
    }
}
