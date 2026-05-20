<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PerformanceTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $employee = Employee::where('username', 'avelez')->first();
        if (! $employee) {
            return;
        }

        $date = Carbon::parse('2026-04-29');

        // Delete existing for this date to avoid duplicates
        AgentStateTransition::where('employee_id', $employee->id)
            ->whereDate('transition_time', $date)
            ->delete();

        $transitions = [
            // Login
            ['state' => 'Logged-in', 'time' => '05:58:00', 'duration' => 0, 'reason' => null],
            ['state' => 'Not Ready', 'time' => '05:58:01', 'duration' => 120, 'reason' => 'Agent Logon'],

            // Ready
            ['state' => 'Ready', 'time' => '06:00:01', 'duration' => 3600, 'reason' => null],

            // Talking
            ['state' => 'Talking', 'time' => '07:00:01', 'duration' => 300, 'reason' => null],

            // Break
            ['state' => 'Not Ready', 'time' => '07:30:00', 'duration' => 900, 'reason' => 'Descanso'],

            // Back to Ready
            ['state' => 'Ready', 'time' => '07:45:00', 'duration' => 3600, 'reason' => null],

            // Lunch
            ['state' => 'Not Ready', 'time' => '11:05:00', 'duration' => 2700, 'reason' => 'Almuerzo'],

            // Back to Ready
            ['state' => 'Ready', 'time' => '11:50:00', 'duration' => 7200, 'reason' => null],

            // Logout
            ['state' => 'Logout', 'time' => '15:00:00', 'duration' => 0, 'reason' => null],
        ];

        foreach ($transitions as $t) {
            AgentStateTransition::create([
                'agent_login_id' => $employee->username,
                'employee_id' => $employee->id,
                'transition_time' => $date->copy()->setTimeFromTimeString($t['time']),
                'agent_state' => $t['state'],
                'reason_code' => $t['reason'],
                'duration' => $t['duration'],
            ]);
        }
    }
}
