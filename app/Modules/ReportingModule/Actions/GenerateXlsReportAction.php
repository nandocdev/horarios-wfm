<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Actions;

use Illuminate\Http\Response;

final class GenerateXlsReportAction
{
    public function execute(array $rows, array $headers, string $filename): Response
    {
        $html = view('reporting::reports.xls-table', [
            'headers' => $headers,
            'rows' => $rows,
            'title' => $filename,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.xls\"",
        ]);
    }
}
