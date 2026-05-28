<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\Widgets;

use App\Modules\OperationsModule\Services\PerformanceService;
use Carbon\Carbon;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class HeroKpiWidget extends Component
{
    public string $selectedDate;

    public function mount(string $selectedDate): void
    {
        $this->selectedDate = $selectedDate;
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 xl:grid-cols-6 gap-4 animate-pulse">
            @for ($i = 0; $i < 6; $i++)
                <div class="h-24 bg-zinc-100 dark:bg-zinc-800 rounded-xl"></div>
            @endfor
        </div>
        HTML;
    }

    public function render()
    {
        $heroKpis = app(PerformanceService::class)->getGlobalHeroKpis(Carbon::parse($this->selectedDate)) ?: $this->emptyHeroKpis();

        return view('operations::livewire.widgets.hero-kpi-widget', [
            'heroKpis' => $heroKpis,
        ]);
    }

    private function emptyHeroKpis(): array
    {
        return [
            'coverage' => ['label' => 'Cobertura', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'users'],
            'adherence' => ['label' => 'Adherencia', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'clock'],
            'occupancy' => ['label' => 'Ocupación', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'chart-bar'],
            'service_level' => ['label' => 'Nivel de Servicio', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'phone'],
            'absenteeism' => ['label' => 'Ausentismo', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'user-minus'],
            'shrinkage' => ['label' => 'Shrinkage', 'value' => '0%', 'status' => 'neutral', 'delta' => '0%', 'icon' => 'scissors'],
        ];
    }
}
