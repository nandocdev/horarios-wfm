<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Reports\BaseReport;
use Barryvdh\DomPDF\PDF as DomPDFInstance;
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

        $pdf = $this->buildForm($report);
        $filename = sprintf('%s_%s.pdf', str_replace(' ', '_', $title), now()->format('Ymd_His'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function buildForm(BaseReport $report): DomPDFInstance
    {
        $data = $report->data();
        $authUser = auth()->user();

        $html = $report->view()->with(array_merge($data, [
            'title' => $report->title,
            'watermark' => $report->watermark,
            'footer' => $report->footer,
            'header' => $report->header,
            'logo' => public_path('img/logo_full.png'),
            'date' => now()->format('d/m/Y'),
            'user' => $authUser?->name ?? 'Sistema',
            'userRole' => $authUser?->roles->first()?->name ?? '—',
            'filters' => $report->filters,
        ]))->render();

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper('A4', 'portrait');

        $canvas = $pdf->getCanvas();
        $canvas->page_text(72, $canvas->get_height() - 24, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, [100, 116, 139]);

        return $pdf;
    }
}
