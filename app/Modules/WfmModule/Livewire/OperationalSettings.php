<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\ConnectModule\Models\CallQueue;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OperationalSettings extends Component
{
    public array $settings = [];

    public array $queues = [];

    public function mount()
    {
        $this->loadSettings();
        $this->loadQueues();
    }

    public function loadSettings()
    {
        $this->settings = DB::table('operational_settings')
            ->orderBy('key')
            ->get()
            ->map(function ($item) {
                $arr = (array) $item;
                // Determinar si mostrar en minutos o segundos
                $isSeconds = str_ends_with($arr['key'], '_threshold') && (int) $arr['value'] < 300;
                // Excepción: personal_time_threshold se prefiere en minutos si es grande
                if ($arr['key'] === 'personal_time_threshold' && (int) $arr['value'] >= 60) {
                    $isSeconds = false;
                }
                
                if (str_contains($arr['key'], '_minutes')) {
                    $isSeconds = false;
                }

                $arr['unit'] = $isSeconds ? 'segundos' : 'minutos';
                $arr['display_value'] = $isSeconds ? (int) $arr['value'] : round((int) $arr['value'] / 60, 1);
                
                return $arr;
            })
            ->toArray();
    }

    public function loadQueues()
    {
        $this->queues = CallQueue::orderBy('name')
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'name' => $q->name,
                'aht_goal' => $q->aht_goal,
            ])
            ->toArray();
    }

    public function save()
    {
        if (! auth()->user()->hasAnyRole(['admin', 'wfm', 'super-admin'])) {
            abort(403, 'No tienes permisos suficientes para modificar la configuración operativa global.');
        }

        DB::transaction(function () {
            foreach ($this->settings as $setting) {
                // Convertir de vuelta a segundos según la unidad mostrada
                $valueInSeconds = $setting['unit'] === 'minutos' 
                    ? (int) ($setting['display_value'] * 60) 
                    : (int) $setting['display_value'];
                
                DB::table('operational_settings')
                    ->where('id', $setting['id'])
                    ->update(['value' => (string) $valueInSeconds]);
            }

            foreach ($this->queues as $queue) {
                CallQueue::where('id', $queue['id'])
                    ->update(['aht_goal' => $queue['aht_goal']]);
            }
        });

        $this->loadSettings(); // Recargar para reflejar cambios y unidades correctas
        \Flux::toast('Configuraciones guardadas correctamente.');
    }

    public function render()
    {
        return view('wfm::livewire.operational-settings');
    }
}
