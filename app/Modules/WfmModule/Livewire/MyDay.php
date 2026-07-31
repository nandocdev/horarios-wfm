<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class MyDay extends Component
{
    public string $selectedDate;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->toDateString();
        $this->dispatch('my-day-date-changed', date: $this->selectedDate);
    }

    public function nextDay(): void
    {
        $next = Carbon::parse($this->selectedDate)->addDay();
        if ($next->lte(now())) {
            $this->selectedDate = $next->toDateString();
            $this->dispatch('my-day-date-changed', date: $this->selectedDate);
        }
    }

    public function render()
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            return $this->emptyView();
        }

        return view('wfm::livewire.my-day', [
            'employeeId' => $employee->id,
            'isHistorical' => Carbon::parse($this->selectedDate)->isPast() && Carbon::parse($this->selectedDate)->toDateString() !== now()->toDateString(),
        ])->layout('layouts.app', ['title' => 'Mi Jornada']);
    }

    private function emptyView(): View
    {
        return view('wfm::livewire.my-day', [
            'employeeId' => null,
            'isHistorical' => false,
        ])->layout('layouts.app', ['title' => 'Mi Jornada']);
    }
}
