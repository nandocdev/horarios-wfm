<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class GenerateXlsReportAction
{
    public function execute(array $rows, array $headers, string $filename): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($rows, $headers, $filename): void {
                echo view('reporting::reports.xls-table', [
                    'headers' => $headers,
                    'rows' => $rows,
                    'title' => $filename,
                ])->render();
            },
            "{$filename}.xls",
            ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8'],
        );
    }
}
