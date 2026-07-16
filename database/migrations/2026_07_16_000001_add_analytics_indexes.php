<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Índices para consultas de tiempo real por fecha
        DB::statement('CREATE INDEX IF NOT EXISTS idx_call_records_ivr_started ON call_records(ivr_started_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_call_records_status ON call_records(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_call_records_queue_ivr ON call_records(queue_id, ivr_started_at)');

        // Índices para consultas de agente por fecha
        DB::statement('CREATE INDEX IF NOT EXISTS idx_agent_call_performance_start ON agent_call_performance(start_time)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_agent_call_performance_emp_start ON agent_call_performance(employee_id, start_time)');

        // Índices para transiciones de estado
        DB::statement('CREATE INDEX IF NOT EXISTS idx_agent_state_trans_time ON agent_state_transitions(transition_time)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_agent_state_trans_emp_time ON agent_state_transitions(employee_id, transition_time)');

        // Índices para horarios
        DB::statement('CREATE INDEX IF NOT EXISTS idx_weekly_schedule_assignments_lookup ON weekly_schedule_assignments(weekly_schedule_id, employee_id, day_of_week)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_call_records_ivr_started');
        DB::statement('DROP INDEX IF EXISTS idx_call_records_status');
        DB::statement('DROP INDEX IF EXISTS idx_call_records_queue_ivr');
        DB::statement('DROP INDEX IF EXISTS idx_agent_call_performance_start');
        DB::statement('DROP INDEX IF EXISTS idx_agent_call_performance_emp_start');
        DB::statement('DROP INDEX IF EXISTS idx_agent_state_trans_time');
        DB::statement('DROP INDEX IF EXISTS idx_agent_state_trans_emp_time');
        DB::statement('DROP INDEX IF EXISTS idx_weekly_schedule_assignments_lookup');
    }
};
