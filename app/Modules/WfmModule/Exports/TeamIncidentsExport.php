<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Exports;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;

final class TeamIncidentsExport
{
    /**
     * Exporta las incidencias del equipo como XLS (HTML table).
     *
     * @param  Collection<int, object>  $exceptions  grouped by employee_id
     */
    public function toXls(
        Collection $exceptions,
        string $teamName,
        string $periodLabel,
    ): Response {
        $filename = sprintf('incidencias_%s_%s.xls', str_replace(' ', '_', $teamName), now()->format('Ymd_His'));

        $rows = '';
        foreach ($exceptions as $employeeId => $empExceptions) {
            foreach ($empExceptions as $ex) {
                $rows .= '<tr>';
                $rows .= '<td>'.e($ex->employee?->full_name ?? "Empleado #{$employeeId}").'</td>';
                $rows .= '<td>'.e($ex->reason?->name ?? '—').'</td>';
                $rows .= '<td>'.e($ex->start_at?->format('d/m/Y') ?? '—').'</td>';
                $rows .= '<td>'.e($ex->start_at?->format('H:i') ?? '—').'</td>';
                $rows .= '<td>'.e($ex->end_at?->format('H:i') ?? '—').'</td>';
                $rows .= '<td>'.($ex->is_full_day ? 'Sí' : 'No').'</td>';
                $rows .= '<td>'.e($ex->remarks ?? '—').'</td>';
                $rows .= '</tr>';
            }
        }

        if (empty($rows)) {
            $rows = '<tr><td colspan="7" style="text-align:center;color:#9ca3af;">Sin incidencias en el período</td></tr>';
        }

        $html = <<<HTML
        <table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;font-family:sans-serif;font-size:12px;">
            <thead>
                <tr style="background:#f3f4f6;">
                    <th>Empleado</th>
                    <th>Causa</th>
                    <th>Fecha</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Día completo</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>
        <p style="font-size:11px;color:#6b7280;margin-top:8px;">
            Equipo: {$teamName} | Período: {$periodLabel} | Generado: {{ now()->format('d/m/Y H:i') }}
        </p>
        HTML;

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}
