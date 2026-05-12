<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\Schedule;
use Illuminate\Support\Facades\DB;

class CreateScheduleAction
{
    public function execute(array $data): Schedule
    {
        return DB::transaction(function () use ($data) {
            return Schedule::create([
                'name' => $data['name'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'total_minutes' => $data['total_minutes'],
                'break_minutes' => $data['break_minutes'] ?? 30,
                'lunch_minutes' => $data['lunch_minutes'] ?? 60,
                'is_lunch_paid' => $data['is_lunch_paid'] ?? false,
                'is_break_paid' => $data['is_break_paid'] ?? true,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }
}
