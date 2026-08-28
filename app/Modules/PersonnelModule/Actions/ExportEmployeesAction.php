<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\DTOs\EmployeeExportDTO;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

final class ExportEmployeesAction
{
    public function execute(EmployeeExportDTO $dto): Response
    {
        $employees = $this->buildQuery($dto)->orderBy('last_name')->orderBy('first_name')->get();

        if ($dto->format === 'excel') {
            $filename = sprintf('employees_%s.xls', now()->format('Ymd_His'));
            $content = $this->toHtmlTable($employees);

            return response($content)
                ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        }

        $filename = sprintf('employees_%s.csv', now()->format('Ymd_His'));
        $content = $this->toCsv($employees);

        return response($content)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function buildQuery(EmployeeExportDTO $dto): Builder
    {
        return Employee::query()
            ->with(['team', 'position', 'status', 'department'])
            ->when($dto->search, function (Builder $query) use ($dto) {
                $query->where(function (Builder $q) use ($dto) {
                    $q->where('first_name', 'ilike', "%{$dto->search}%")
                        ->orWhere('last_name', 'ilike', "%{$dto->search}%")
                        ->orWhere('employee_number', 'ilike', "%{$dto->search}%")
                        ->orWhere('email', 'ilike', "%{$dto->search}%");
                });
            })
            ->when(! is_null($dto->departmentId), fn (Builder $query) => $query->where('department_id', $dto->departmentId))
            ->when(! is_null($dto->positionId), fn (Builder $query) => $query->where('position_id', $dto->positionId))
            ->when(! is_null($dto->employmentStatusId), fn (Builder $query) => $query->where('employment_status_id', $dto->employmentStatusId))
            ->when(! is_null($dto->isActive), fn (Builder $query) => $query->where('is_active', $dto->isActive))
            ->when(! is_null($dto->isManager), fn (Builder $query) => $query->where('is_manager', $dto->isManager))
            ->when(! is_null($dto->dateFrom), fn (Builder $query) => $query->whereDate('hire_date', '>=', $dto->dateFrom))
            ->when(! is_null($dto->dateTo), fn (Builder $query) => $query->whereDate('hire_date', '<=', $dto->dateTo))
            ->when(! $dto->all && ! empty($dto->selected), fn (Builder $query) => $query->whereIn('id', $dto->selected));
    }

    private function toCsv(Collection $employees): string
    {
        $headers = [
            'employee_number',
            'name',
            'email',
            'department',
            'position',
            'team',
            'status',
            'hire_date',
            'active',
            'manager',
        ];

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        foreach ($employees as $employee) {
            fputcsv($handle, [
                $employee->employee_number,
                $employee->full_name,
                $employee->email,
                $employee->department?->name,
                $employee->position?->name,
                $employee->team?->name,
                $employee->status?->name,
                optional($employee->hire_date)?->format('Y-m-d'),
                $employee->is_active ? '1' : '0',
                $employee->is_manager ? '1' : '0',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    private function toHtmlTable(Collection $employees): string
    {
        $rows = $employees->map(function (Employee $employee): string {
            return sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                e($employee->employee_number),
                e($employee->full_name),
                e($employee->email),
                e($employee->department?->name),
                e($employee->position?->name),
                e($employee->team?->name),
                e($employee->status?->name),
                e(optional($employee->hire_date)?->format('Y-m-d')),
                $employee->is_active ? '1' : '0',
                $employee->is_manager ? '1' : '0',
            );
        })->implode('');

        return <<<HTML
<table>
    <thead>
        <tr>
            <th>employee_number</th>
            <th>name</th>
            <th>email</th>
            <th>department</th>
            <th>position</th>
            <th>team</th>
            <th>status</th>
            <th>hire_date</th>
            <th>active</th>
            <th>manager</th>
        </tr>
    </thead>
    <tbody>{$rows}</tbody>
</table>
HTML;
    }
}
