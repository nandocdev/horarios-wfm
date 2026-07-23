<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Reports\BaseReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GenerateFormPdfAction
{
    /**
     * Genera un PDF de formulario institucional (A4, orientación portrait).
     *
     * @param  array<string, mixed>  $data  Variables para la vista
     * @param  string  $view  Nombre de la vista Blade (namespace::view)
     * @param  string  $title  Título del documento
     */
    public function execute(array $data, string $view, string $title): StreamedResponse
    {
        $report = new class($data, $view, $title) extends BaseReport
        {
            protected string $paperSize = 'A4';

            public function __construct(
                private readonly array $data,
                private readonly string $view,
                string $title,
            ) {
                parent::__construct();
                $this->title($title);
                $this->orientation('portrait');
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

        $pdf = $report->build();
        $filename = sprintf('%s_%s.pdf', str_replace(' ', '_', $title), now()->format('Ymd_His'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
