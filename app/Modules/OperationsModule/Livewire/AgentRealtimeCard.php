<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Shared\Contracts\WfmModule\ExpectedAgentStateInterface;
use App\Shared\DTOs\AdherenceStatusDTO;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AgentRealtimeCard extends Component
{
    public $employeeId;

    public $ciscoUsername;

    public function render()
    {
        $now = now();

        // 1. Sincronización (Desactivada: Dependemos del background worker cisco:sync)
        // La sincronización síncrona con CUIC causaba errores 500 y lentitud.
        // El trabajador en segundo plano ya mantiene actualizada la tabla agent_realtime_states.

        // 2. Obtener Estados
        $expected = app(ExpectedAgentStateInterface::class)->execute((int) $this->employeeId, $now);

        $realtime = DB::table('agent_realtime_states as ars')
            ->leftJoin('agent_states as as', 'ars.current_state', '=', 'as.external_code')
            ->where('ars.employee_id', $this->employeeId)
            ->select(['ars.*', 'as.display_name', 'as.color_hex', 'as.is_productive'])
            ->first();

        // 3. Procesar Adherencia vía DTO y convertir a plano
        $adherenceDto = AdherenceStatusDTO::fromStates($expected, $realtime);
        $adherence = (object) [
            'isAdherent' => $adherenceDto->isAdherent,
            'label' => $adherenceDto->label,
            'color' => $adherenceDto->color,
            'description' => $adherenceDto->description,
        ];

        return view('operations::livewire.agent-realtime-card', [
            'realtime' => $realtime,
            'expected' => $expected,
            'adherence' => $adherence,
        ]);
    }
}
