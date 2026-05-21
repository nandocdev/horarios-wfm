<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\ConnectModule\Models\CallQueue;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OperationalSettings extends Component
{
    public array $settings = [];

    public array $thresholds = [];

    public array $kpiGoals = [];

    public array $queues = [];

    public ?string $newGoalKey = '';
    public ?string $newGoalLabel = '';

    public function mount()
    {
        $this->loadSettings();
        $this->loadQueues();
    }

    public function loadSettings()
    {
        $allSettings = DB::table('operational_settings')
            ->orderBy('key')
            ->get()
            ->map(function ($item) {
                $arr = (array) $item;
                $category = $arr['category'] ?? 'threshold';

                if ($category === 'threshold') {
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
                } else {
                    $arr['unit'] = '%';
                    $arr['display_value'] = (float) $arr['value'];
                }

                return $arr;
            });

        $this->thresholds = $allSettings->where('category', 'threshold')->values()->toArray();
        $this->kpiGoals = $allSettings->where('category', 'kpi_goal')->values()->toArray();
    }

    public function addGoal()
    {
        $this->validate([
            'newGoalKey' => 'required|alpha_dash|unique:operational_settings,key',
            'newGoalLabel' => 'required|string|max:100',
        ]);

        $key = str_starts_with($this->newGoalKey, 'goal_') ? $this->newGoalKey : 'goal_' . $this->newGoalKey;

        DB::table('operational_settings')->insert([
            'key' => $key,
            'value' => '0',
            'description' => $this->newGoalLabel,
            'category' => 'kpi_goal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->reset(['newGoalKey', 'newGoalLabel']);
        $this->loadSettings();
        \Flux::toast('Nueva meta agregada.');
    }

    public function removeGoal($id)
    {
        DB::table('operational_settings')->where('id', $id)->delete();
        $this->loadSettings();
        \Flux::toast('Meta eliminada.');
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
            foreach ($this->thresholds as $setting) {
                // Convertir de vuelta a segundos según la unidad mostrada
                $valueInSeconds = $setting['unit'] === 'minutos'
                    ? (int) ($setting['display_value'] * 60)
                    : (int) $setting['display_value'];

                DB::table('operational_settings')
                    ->where('id', $setting['id'])
                    ->update(['value' => (string) $valueInSeconds]);
            }

            foreach ($this->kpiGoals as $goal) {
                DB::table('operational_settings')
                    ->where('id', $goal['id'])
                    ->update(['value' => (string) $goal['display_value']]);
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
