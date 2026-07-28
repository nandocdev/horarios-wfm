<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportProductionDataSeeder extends Seeder
{
    private string $dataDir;

    public function __construct()
    {
        $this->dataDir = database_path('data/ecm_db');
    }

    public function run(): void
    {
        $this->command->info('Importando datos de produccion desde ecm_db/...');
        $this->command->warn('Este seeder debe ejecutarse sobre una BD recien migrada (tablas vacias).');

        // Batch 1 — Geografia
        $this->import('provinces');
        $this->import('districts');
        $this->import('townships');

        // Batch 2 — Organizacion
        $this->import('directorates');
        $this->import('departments');
        $this->import('positions');
        $this->import('employment_statuses');

        // Batch 3 — RBAC
        $this->import('roles');
        $this->import('permissions');
        $this->importPivot('role_has_permissions', ['permission_id', 'role_id']);
        $this->importPivot('model_has_roles', ['role_id', 'model_type', 'model_id']);

        // Batch 4 — Usuarios
        $this->import('users');

        // Batch 5 — Empleados (sin team_id por FK circular con teams)
        // teams.supervisor_id → employees.id  y  employees.team_id → teams.id
        $employeeTeams = $this->importEmployeesWithoutTeam();

        // Batch 6 — Equipos
        $this->import('teams');
        $this->import('team_members');
        $this->updateEmployeeTeams($employeeTeams);

        // Batch 7 — Catalogos
        $this->import('absence_reason_codes');
        $this->import('activity_types');
        $this->import('incident_types');
        $this->import('agent_states');
        $this->import('channels');
        $this->import('call_queues');
        $this->import('case_subtypes');
        $this->import('schedules');
        $this->import('scheduled_activity_definitions');
        $this->import('helpdesk_categories');
        $this->import('operational_settings', 'key');
        $this->import('approved_intraday_periods');

        // Batch 8 — Semanas y asignaciones
        $this->import('weekly_schedules');
        $this->import('weekly_schedule_assignments');
        $this->import('weekly_team_assignments');

        // Batch 9 — Excepciones, permisos, cambios turno
        $this->import('schedule_exceptions');
        $this->import('leave_requests');
        $this->import('leave_request_approvals');
        $this->import('shift_swap_requests');
        $this->import('shift_swap_approvals');

        // Batch 10 — Telemetria y desempeno
        $this->import('agent_realtime_states');
        $this->import('agent_daily_metrics');
        $this->import('csq_realtime_stats');
        $this->import('temporal_assignments');

        // Batch 11 — Recursos humanos complementarios
        $this->import('employee_dependents');
        $this->import('employee_disabilities');
        $this->import('employee_diseases');
        $this->import('employee_positions');
        $this->import('employee_import_batches');

        // Batch 12 — Filesystem
        $this->import('folders');
        $this->import('files');
        $this->import('file_shares');
        $this->import('storage_quotas');

        // Batch 13 — Media
        $this->import('media');

        // Batch 14 — Documentacion
        $this->import('documentation_articles');

        // Batch 15 — Helpdesk (depende de employees + categories)
        $this->import('helpdesk_tickets');
        $this->import('helpdesk_ticket_comments');

        // Ajustar secuencias
        $this->resetSequences();

        $this->command->info('Importacion completada.');
    }

    private function import(string $table, string|array $uniqueBy = 'id'): void
    {
        $csvFile = $this->findCsv($table);

        if ($csvFile === null) {
            $this->command->warn("  [skip] {$table}: CSV no encontrado");

            return;
        }

        $rows = $this->parseCsv($csvFile);

        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy);
        }

        $this->command->info("  [ok] {$table}: ".count($rows).' registros');
    }

    private function importPivot(string $table, array $uniqueBy): void
    {
        $csvFile = $this->findCsv($table);

        if ($csvFile === null) {
            $this->command->warn("  [skip] {$table}: CSV no encontrado");

            return;
        }

        $rows = $this->parseCsv($csvFile);

        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy);
        }

        $this->command->info("  [ok] {$table}: ".count($rows).' registros');
    }

    private function importEmployeesWithoutTeam(): array
    {
        $csvFile = $this->findCsv('employees');

        if ($csvFile === null) {
            $this->command->warn('  [skip] employees: CSV no encontrado');

            return [];
        }

        $rows = $this->parseCsv($csvFile);

        if (empty($rows)) {
            return [];
        }

        $teamMap = [];

        foreach (array_chunk($rows, 500) as $chunk) {
            $mutated = [];

            foreach ($chunk as $row) {
                $teamMap[$row['id']] = $row['team_id'] ?? null;
                unset($row['team_id']);
                $mutated[] = $row;
            }

            DB::table('employees')->upsert($mutated, 'id');
        }

        $this->command->info('  [ok] employees: '.count($rows).' registros (sin team_id)');

        return $teamMap;
    }

    private function updateEmployeeTeams(array $teamMap): void
    {
        $count = 0;

        foreach ($teamMap as $employeeId => $teamId) {
            if ($teamId === null) {
                continue;
            }

            DB::table('employees')
                ->where('id', $employeeId)
                ->update(['team_id' => $teamId]);

            $count++;
        }

        if ($count > 0) {
            $this->command->info("  [ok] employees: {$count} team_id asignados");
        }
    }

    private function findCsv(string $table): ?string
    {
        $files = glob($this->dataDir.'/'.$table.'_*.csv');

        return $files[0] ?? null;
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $rawHeader = fgetcsv($handle);

        if ($rawHeader === false || $rawHeader === null) {
            fclose($handle);

            return [];
        }

        $header = array_map(fn (string $col): string => trim($col, '"'), $rawHeader);

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($header)) {
                continue;
            }

            $row = array_combine($header, $data);

            $row = array_map(fn ($value) => $value === '' ? null : $value, $row);

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function resetSequences(): void
    {
        $sequences = DB::select("
            SELECT sequence_name
            FROM information_schema.sequences
            WHERE sequence_schema = 'public'
              AND sequence_name LIKE '%_id_seq'
              AND sequence_name NOT IN (
                  SELECT sequence_name FROM information_schema.sequences
                  WHERE sequence_name LIKE 'quality_%'
                     OR sequence_name LIKE 'knowledge_%'
              )
        ");

        foreach ($sequences as $seq) {
            $seqName = $seq->sequence_name;
            $tableName = preg_replace('/_id_seq$/', '', $seqName);

            DB::statement("SELECT setval('{$seqName}', COALESCE((SELECT MAX(id) FROM {$tableName}), 1))");
        }
    }
}
