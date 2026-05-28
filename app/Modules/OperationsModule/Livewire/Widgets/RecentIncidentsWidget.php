<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\Widgets;

use App\Modules\OperationsModule\Models\AttendanceIncident;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class RecentIncidentsWidget extends Component
{
    public function placeholder()
    {
        return <<<'HTML'
        <div class="h-[300px] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl animate-pulse"></div>
        HTML;
    }

    public function render()
    {
        $incidents = AttendanceIncident::with(['employee', 'type'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($incident) => (object) [
                'first_name' => $incident->employee->first_name,
                'last_name' => $incident->employee->last_name,
                'type' => $incident->type->name,
                'created_at' => $incident->created_at->toDateTimeString(),
            ])
            ->toArray();

        return view('operations::livewire.widgets.recent-incidents-widget', [
            'recentIncidents' => $incidents,
        ]);
    }
}
