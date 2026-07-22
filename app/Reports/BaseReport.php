<?php

declare(strict_types=1);

namespace App\Reports;

use Barryvdh\DomPDF\PDF as DomPDFInstance;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;

abstract class BaseReport
{
    protected string $title = 'Reporte';

    protected string $orientation = 'portrait';

    protected string $paperSize = 'letter';

    protected ?string $watermark = null;

    protected array $footer = [
        'left' => 'Sistema WFM — Caja de Seguro Social de Panamá',
        'right' => 'Generado: {date}',
    ];

    protected array $header = [];

    protected array $filters = [];

    public function __construct()
    {
        $this->footer['right'] = str_replace(
            '{date}',
            now()->format('d/m/Y H:i'),
            $this->footer['right'],
        );
    }

    abstract public function data(): array;

    abstract public function view(): View;

    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function orientation(string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    public function withFilters(array $filters): static
    {
        $this->filters = $filters;

        return $this;
    }

    public function withWatermark(string $text): static
    {
        $this->watermark = $text;

        return $this;
    }

    public function download(?string $filename = null): Response
    {
        $filename ??= str_replace(' ', '_', $this->title).'_'.now()->format('Ymd_His').'.pdf';

        return $this->build()
            ->download($filename);
    }

    public function stream(?string $filename = null): Response
    {
        $filename ??= str_replace(' ', '_', $this->title).'_'.now()->format('Ymd_His').'.pdf';

        return $this->build()
            ->stream($filename);
    }

    public function build(): DomPDFInstance
    {
        $data = $this->data();
        $authUser = auth()->user();

        $html = $this->view()->with(array_merge($data, [
            'title' => $this->title,
            'watermark' => $this->watermark,
            'footer' => $this->footer,
            'header' => $this->header,
            'logo' => public_path('img/logo_full.png'),
            'date' => now()->format('d/m/Y H:i'),
            'user' => $authUser?->name ?? 'Sistema',
            'userRole' => $authUser?->roles->first()?->name ?? '—',
            'filters' => $this->filters,
        ]))->render();

        $pdf = App::make('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper($this->paperSize, $this->orientation);

        $canvas = $pdf->getCanvas();
        $canvas->page_text(72, $canvas->get_height() - 24, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, [100, 116, 139]);

        return $pdf;
    }

    protected function formatSeconds(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return $hours > 0
            ? sprintf('%dh %02dm', $hours, $minutes)
            : sprintf('%dm %02ds', $minutes, $secs);
    }

    protected function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours > 0
            ? sprintf('%dh %02dm', $hours, $mins)
            : sprintf('%dm', $mins);
    }
}
