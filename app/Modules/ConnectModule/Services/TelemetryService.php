<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Services;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Shared\Contracts\Telemetry\TelemetryServiceInterface;
use App\Shared\DTOs\Telemetry\TelemetryStateDTO;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class TelemetryService implements TelemetryServiceInterface
{
    public function getCurrentState(int $employeeId): ?TelemetryStateDTO
    {
        $state = AgentRealtimeState::where('employee_id', $employeeId)->first();
        if (!$state) return null;

        return new TelemetryStateDTO(
            $employeeId,
            $state->current_state ?? 'OFFLINE',
            $state->reason_code,
            $state->last_changed_at instanceof \Illuminate\Support\Carbon 
                ? $state->last_changed_at->utc()->toIso8601String() 
                : \Illuminate\Support\Carbon::parse((string) $state->last_changed_at)->utc()->toIso8601String(),
            (array) $state->metadata
        );
    }

    public function getBatchCurrentStates(array $employeeIds): array
    {
        if (empty($employeeIds)) return [];
        $states = AgentRealtimeState::whereIn('employee_id', $employeeIds)->get()->keyBy('employee_id');

        $results = [];
        foreach ($employeeIds as $id) {
            $state = $states[$id] ?? null;
            if ($state) {
                $results[$id] = new TelemetryStateDTO(
                    $id,
                    $state->current_state ?? 'OFFLINE',
                    $state->reason_code,
                    $state->last_changed_at instanceof \Illuminate\Support\Carbon 
                        ? $state->last_changed_at->utc()->toIso8601String() 
                        : \Illuminate\Support\Carbon::parse((string) $state->last_changed_at)->utc()->toIso8601String(),
                    (array) $state->metadata
                );
            }
        }
        return $results;
    }

    public function getStateTransitions(int $employeeId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return AgentStateTransition::where('employee_id', $employeeId)
            ->where('transition_time', '>=', $start)
            ->where('transition_time', '<=', $end)
            ->orderBy('transition_time')
            ->get()
            ->map(fn($t) => new TelemetryStateDTO(
                $employeeId,
                $t->agent_state,
                $t->reason_code,
                $t->transition_time instanceof \Illuminate\Support\Carbon ? $t->transition_time->toIso8601String() : (string) $t->transition_time,
                [
                    'duration' => $t->duration,
                    'is_productive' => in_array($t->agent_state, ['Ready', 'Reserved', 'Talking', 'Work'])
                ]
            ));
    }
}
