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

        // Batch 4 — Usuarios y empleados
        $this->import('users');
        $this->import('employees');

        // Batch 5 — Equipos
        $this->import('teams');
        $this->import('team_members');

        // Batch 6 — Catalogos
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
        $this->import('operational_settings');
        $this->import('approved_intraday_periods');

        // Batch 7 — Semanas y asignaciones
        $this->import('weekly_schedules');
        $this->import('weekly_schedule_assignments');
        $this->import('weekly_team_assignments');

        // Batch 8 — Excepciones, permisos, cambios turno
        $this->import('schedule_exceptions');
        $this->import('leave_requests');
        $this->import('leave_request_approvals');
        $this->import('shift_swap_requests');
        $this->import('shift_swap_approvals');

        // Batch 9 — Telemetria y desempeno
        $this->import('agent_realtime_states');
        $this->import('agent_daily_metrics');
        $this->import('csq_realtime_stats');
        $this->import('temporal_assignments');

        // Batch 10 — Recursos humanos complementarios
        $this->import('employee_dependents');
        $this->import('employee_disabilities');
        $this->import('employee_diseases');
        $this->import('employee_positions');
        $this->import('employee_import_batches');

        // Batch 11 — Filesystem
        $this->import('folders');
        $this->import('files');
        $this->import('file_shares');
        $this->import('storage_quotas');

        // Batch 12 — Media
        $this->import('media');

        // Batch 13 — Documentacion
        $this->import('documentation_articles');

        // Batch 14 — Helpdesk (depende de employees + categories)
        $this->import('helpdesk_tickets');
        $this->import('helpdesk_ticket_comments');

        // Ajustar secuencias
        $this->resetSequences();

        $this->command->info('Importacion completada.');
    }

    private function import(string $table): void
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
            DB::table($table)->upsert($chunk, 'id');
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
