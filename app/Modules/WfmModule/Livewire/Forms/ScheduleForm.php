<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\Forms;

use App\Modules\WfmModule\Models\Schedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Rule;
use Livewire\Form;

class ScheduleForm extends Form
{
    public ?Schedule $schedule = null;

    #[Rule('required|string|max:100')]
    public string $name = '';

    #[Rule('required')]
    public string $start_time = '';

    #[Rule('required')]
    public string $end_time = '';

    #[Rule('required|integer|min:1')]
    public int $total_minutes = 0;

    #[Rule('required|integer|min:0')]
    public int $break_minutes = 30;

    #[Rule('required|integer|min:0')]
    public int $lunch_minutes = 60;

    #[Rule('required|array|min:1')]
    public array $allowed_days = [1, 2, 3, 4, 5, 6, 7];

    public bool $is_lunch_paid = false;

    public bool $is_break_paid = true;

    public bool $is_active = true;

    public function setSchedule(Schedule $schedule): void
    {
        $this->schedule = $schedule;
        $this->name = $schedule->name;
        $this->start_time = $schedule->start_time instanceof CarbonInterface
            ? $schedule->start_time->format('H:i')
            : (string) $schedule->start_time;
        $this->end_time = $schedule->end_time instanceof CarbonInterface
            ? $schedule->end_time->format('H:i')
            : (string) $schedule->end_time;
        $this->total_minutes = $schedule->total_minutes;
        $this->break_minutes = $schedule->break_minutes;
        $this->lunch_minutes = $schedule->lunch_minutes;
        $this->is_lunch_paid = $schedule->is_lunch_paid;
        $this->is_break_paid = $schedule->is_break_paid;
        $this->is_active = $schedule->is_active;
        $this->allowed_days = $schedule->allowed_days ?? [1, 2, 3, 4, 5, 6, 7];
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'start_time', 'end_time', 'total_minutes', 'break_minutes', 'lunch_minutes', 'is_lunch_paid', 'is_break_paid', 'is_active', 'allowed_days', 'schedule']);
    }

    public function calculateTotalMinutes(): void
    {
        if ($this->start_time && $this->end_time) {
            try {
                $start = Carbon::parse($this->start_time);
                $end = Carbon::parse($this->end_time);

                if ($end->lessThan($start)) {
                    $end = $end->addDay();
                }

                $this->total_minutes = (int) $start->diffInMinutes($end);
            } catch (\Exception $e) {
                $this->total_minutes = 0;
            }
        }
    }
}
