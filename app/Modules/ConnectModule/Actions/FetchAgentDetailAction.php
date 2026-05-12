<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Services\CuicReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Obtiene el detalle de agentes desde el reporte CUIC `agent_detail`.
 *
 * Requiere el flujo de 2 pasos:
 *   1. POST /filter → define el rango horario y la lista de agentes
 *   2. GET  /execute → obtiene los datos
 *
 * Ejemplo de uso:
 *   // Rango completo del día
 *   $rows = app(FetchAgentDetailAction::class)->execute(Carbon::yesterday());
 *
 *   // Rango horario específico (ej. 06:00–07:00 del día de ayer)
 *   $rows = app(FetchAgentDetailAction::class)->execute(
 *       Carbon::yesterday()->setTime(6, 0, 0),
 *       Carbon::yesterday()->setTime(7, 0, 0),
 *       ['Amalia Renteria', 'Fernando Castillo Valdez']
 *   );
 *
 * [RIESGOS]
 * - La lista de agentes en CUIC usa NOMBRES COMPLETOS (no loginId).
 * - Si $agentNames está vacío, CUIC retorna TODOS los agentes del servidor.
 * - Si $endDateTime es null, se asume fin del día (23:59:59) de $startDateTime.
 */
final class FetchAgentDetailAction
{
    public function __construct(
        private readonly CuicReportService $cuic
    ) {}

    /**
     * Ejecuta el reporte con filtro de rango horario y agentes opcionales.
     *
     * @param  Carbon              $startDateTime  Inicio del rango (fecha + hora)
     * @param  Carbon|null         $endDateTime    Fin del rango (null = fin del día de $startDateTime)
     * @param  array<int, string>  $agentNames     Nombres completos tal como aparecen en CUIC
     * @return Collection<int, array<string, mixed>>
     *
     * @throws \RuntimeException Si CUIC falla o la config está incompleta.
     */
    public function execute(
        Carbon  $startDateTime,
        ?Carbon $endDateTime = null,
        array   $agentNames  = []
    ): Collection {
        $end = $endDateTime ?? (clone $startDateTime)->setTime(23, 59, 59);

        return $this->cuic->executeReportWithFilter(
            reportKey:     'agent_detail',
            startDateTime: $startDateTime,
            endDateTime:   $end,
            agentNames:    $agentNames,
        );
    }
}
