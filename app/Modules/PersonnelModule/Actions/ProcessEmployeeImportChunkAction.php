<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmployeeImportBatch;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProcessEmployeeImportChunkAction
{
    /** @var array<int, int> */
    private array $positionCache = [];

    /** @var array<int, int> */
    private array $teamCache = [];

    /** @var array<int, int> */
    private array $statusCache = [];

    /** @var array<int, int> */
    private array $departmentCache = [];

    /**
     * @param  array<int, array<string, string|null>>  $rows
     */
    public function execute(string $importBatchId, array $rows, int $startRow): void
    {
        $imported = 0;
        $rejected = 0;
        $errors = [];

        // Pre-cargar caches para evitar N+1
        $this->primeCaches($rows);

        DB::transaction(function () use ($rows, $startRow, &$imported, &$rejected, &$errors): void {
            foreach ($rows as $index => $row) {
                $currentRow = $startRow + $index;
                $savepoint = 'emp_import_'.$index;

                DB::statement("SAVEPOINT {$savepoint}");

                try {
                    $normalized = $this->normalize($row);
                    $this->validateRequiredColumns($normalized);

                    $positionId = (int) $normalized['position_id'];
                    $teamId = (int) $normalized['team_id'];
                    $statusId = (int) $normalized['employment_status_id'];

                    if (! isset($this->positionCache[$positionId])) {
                        throw new \RuntimeException('position_id inexistente');
                    }

                    if (! isset($this->teamCache[$teamId])) {
                        throw new \RuntimeException('team_id inexistente');
                    }

                    if (! isset($this->statusCache[$statusId])) {
                        throw new \RuntimeException('employment_status_id inexistente');
                    }

                    $duplicate = Employee::query()
                        ->where('employee_number', $normalized['employee_number'])
                        ->orWhere('username', $normalized['username'])
                        ->orWhere('email', $normalized['email'])
                        ->exists();

                    if ($duplicate) {
                        throw new \RuntimeException('registro duplicado por employee_number, username o email');
                    }

                    $departmentId = $this->resolveDepartmentId($normalized, $positionId);

                    $employee = Employee::query()->create([
                        'employee_number' => $normalized['employee_number'],
                        'username' => $normalized['username'],
                        'cisco_username' => $normalized['cisco_username'],
                        'first_name' => $normalized['first_name'],
                        'last_name' => $normalized['last_name'],
                        'email' => $normalized['email'],
                        'birth_date' => Arr::get($normalized, 'birth_date') ?: null,
                        'gender' => Arr::get($normalized, 'gender') ?: null,
                        'blood_type' => Arr::get($normalized, 'blood_type') ?: null,
                        'phone' => Arr::get($normalized, 'phone') ?: null,
                        'mobile_phone' => Arr::get($normalized, 'mobile_phone') ?: null,
                        'address' => Arr::get($normalized, 'address') ?: null,
                        'township_id' => $this->toNullableInt(Arr::get($normalized, 'township_id')),
                        'department_id' => $departmentId,
                        'position_id' => $positionId,
                        'team_id' => $teamId,
                        'employment_status_id' => $statusId,
                        'parent_id' => $this->toNullableInt(Arr::get($normalized, 'parent_id')),
                        'user_id' => $this->toNullableInt(Arr::get($normalized, 'user_id')),
                        'hire_date' => Arr::get($normalized, 'hire_date') ?: null,
                        'salary' => Arr::get($normalized, 'salary') ?: null,
                        'is_active' => $this->toBool(Arr::get($normalized, 'is_active'), true),
                        'is_manager' => $this->toBool(Arr::get($normalized, 'is_manager'), false),
                        'metadata' => [],
                    ]);

                    TeamMember::query()->create([
                        'team_id' => $teamId,
                        'employee_id' => $employee->id,
                        'joined_at' => Arr::get($normalized, 'hire_date') ?: now()->toDateString(),
                        'is_active' => true,
                    ]);

                    $imported++;
                    DB::statement("RELEASE SAVEPOINT {$savepoint}");
                } catch (Throwable $exception) {
                    DB::statement("ROLLBACK TO SAVEPOINT {$savepoint}");
                    DB::statement("RELEASE SAVEPOINT {$savepoint}");

                    $rejected++;
                    $errors[] = [
                        'row' => $currentRow,
                        'employee_number' => Arr::get($row, 'employee_number'),
                        'message' => $exception->getMessage(),
                    ];
                }
            }
        });

        DB::transaction(function () use ($importBatchId, $rows, $imported, $rejected, $errors): void {
            /** @var EmployeeImportBatch $batch */
            $batch = EmployeeImportBatch::query()->lockForUpdate()->findOrFail($importBatchId);

            $mergedErrors = array_merge($batch->errors ?? [], $errors);

            $batch->update([
                'processed_rows' => $batch->processed_rows + count($rows),
                'imported_rows' => $batch->imported_rows + $imported,
                'rejected_rows' => $batch->rejected_rows + $rejected,
                'status' => $rejected > 0 ? 'completed_with_errors' : $batch->status,
                'errors' => array_slice($mergedErrors, -1000),
            ]);
        });
    }

    /**
     * @param  array<int, array<string, string|null>>  $rows
     */
    private function primeCaches(array $rows): void
    {
        $positionIds = array_filter(array_unique(array_column($rows, 'position_id')));
        $teamIds = array_filter(array_unique(array_column($rows, 'team_id')));
        $statusIds = array_filter(array_unique(array_column($rows, 'employment_status_id')));
        $departmentIds = array_filter(array_unique(array_column($rows, 'department_id')));

        $this->positionCache = Position::whereIn('id', $positionIds)->pluck('department_id', 'id')->toArray();
        $this->teamCache = Team::whereIn('id', $teamIds)->pluck('id', 'id')->toArray();
        $this->statusCache = EmploymentStatus::whereIn('id', $statusIds)->pluck('id', 'id')->toArray();
        $this->departmentCache = Department::whereIn('id', $departmentIds)->pluck('id', 'id')->toArray();
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, string|null>
     */
    private function normalize(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            // Normalización robusta de encabezados (snake_case)
            $cleanKey = Str::snake(trim((string) $key));
            $normalized[$cleanKey] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function validateRequiredColumns(array $row): void
    {
        foreach (['employee_number', 'username', 'cisco_username', 'first_name', 'last_name', 'email', 'position_id', 'team_id', 'employment_status_id'] as $column) {
            if (! filled($row[$column] ?? null)) {
                throw new \RuntimeException("columna requerida ausente: {$column}");
            }
        }
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function resolveDepartmentId(array $row, int $positionId): ?int
    {
        $departmentId = $this->toNullableInt(Arr::get($row, 'department_id'));

        if ($departmentId) {
            if (! isset($this->departmentCache[$departmentId])) {
                throw new \RuntimeException('department_id inexistente');
            }

            return $departmentId;
        }

        return $this->positionCache[$positionId] ?? null;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if (! filled($value)) {
            return null;
        }

        return (int) $value;
    }

    private function toBool(mixed $value, bool $default): bool
    {
        if (! filled($value)) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'si'], true);
    }
}
