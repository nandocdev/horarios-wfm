<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Services\CuicReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Obtiene las transiciones de estado de agentes desde CUIC para un día dado.
 *
 * Normaliza el campo `transition_time` de epoch milisegundos a Carbon.
 * Normaliza `duration` (ya en segundos, no requiere conversión).
 *
 * Ejemplo de uso:
 *   $rows = app(FetchAgentStateTransitionsAction::class)->execute(Carbon::today());
 *
 * [RIESGOS]
 * - CUIC retorna TODOS los agentes del reporte; filtrar en memoria para volúmenes grandes.
 * - `transition_time` es epoch ms (13 dígitos) → SIEMPRE dividir entre 1000.
 * - Si CUIC no tiene datos del día, retorna Collection vacía (manejado).
 */
final class FetchAgentStateTransitionsAction {
    public function __construct(
        private readonly CuicReportService $cuic
    ) {
    }

    /**
     * Ejecuta el reporte CUIC y retorna las transiciones normalizadas del día.
     * No requiere filtro previo — CUIC retorna todos los agentes del día.
     *
     * @param  Carbon  $date  Fecha a consultar
     * @param  string|null  $loginId  Si se provee, filtra por login_id del agente
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(Carbon $date, ?string $loginId = null): Collection {
        $rows = $this->cuic->executeReport('agent_state_transitions');

        return $rows
            ->filter(fn(array $row) => $this->isOnDate($row, $date))
            ->when($loginId, fn($col) => $col->where('agent_login_id', $loginId))
            ->map(fn(array $row) => $this->normalize($row))
            ->values();
    }

    /**
     * Verifica si la transición pertenece a la fecha solicitada.
     * `transition_time` viene en epoch MILISEGUNDOS.
     *
     * @param  array<string, mixed>  $row
     */
    private function isOnDate(array $row, Carbon $date): bool {
        $transitionMs = (int) ($row['transition_time'] ?? 0);

        if ($transitionMs === 0) {
            return false;
        }

        $transitionDate = Carbon::createFromTimestampMs($transitionMs, 'UTC')->tz(config('app.timezone'));

        return $transitionDate->isSameDay($date);
    }

    /**
     * Normaliza una fila CUIC al formato canónico del sistema.
     *
     * Estructura de entrada (CUIC):
     * {
     *   "agent_name": "Amalia Renteria",
     *   "agent_login_id": "arenteria",
     *   "agent_extension": "37601",
     *   "transition_time": 1777460280351,   ← epoch ms
     *   "agent_state": "Not Ready",
     *   "reason_code": "Asignación Especial",
     *   "duration": 7710                    ← segundos
     * }
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array {
        $transitionMs = (int) ($row['transition_time'] ?? 0);

        return [
            'agent_name' => trim((string) ($row['agent_name'] ?? '')),
            'agent_login_id' => trim((string) ($row['agent_login_id'] ?? '')),
            'agent_extension' => trim((string) ($row['agent_extension'] ?? '')),
            'transition_time' => $transitionMs > 0
                ? Carbon::createFromTimestampMs($transitionMs, 'UTC')->tz(config('app.timezone'))
                : null,
            'agent_state' => trim((string) ($row['agent_state'] ?? '')),
            'reason_code' => isset($row['reason_code']) && $row['reason_code'] !== ''
                ? trim((string) $row['reason_code'])
                : null,
            'duration' => (int) ($row['duration'] ?? 0), // segundos
        ];
    }
}
