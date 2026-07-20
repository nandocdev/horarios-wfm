<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\Forms;

use Livewire\Form;

class IncidentForm extends Form
{
    public ?int $id = null;

    public ?int $employee_id = null;

    public ?string $date = null;

    public ?int $reason_id = null;

    public string $start_time = '08:00';

    public string $end_time = '17:00';

    public bool $is_full_day = true;

    public ?string $remarks = null;

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'reason_id' => ['required', 'integer', 'exists:absence_reason_codes,id'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'is_full_day' => ['boolean'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'employee_id' => 'empleado',
            'date' => 'fecha',
            'reason_id' => 'motivo',
            'remarks' => 'observaciones',
        ];
    }

    public function resetForCreate(int $employeeId, string $date): void
    {
        $this->reset();
        $this->employee_id = $employeeId;
        $this->date = $date;
        $this->is_full_day = true;
        $this->start_time = '08:00';
        $this->end_time = '17:00';
    }

    public function fillForEdit(int $id, int $employeeId, string $date, int $reasonId, string $startTime, string $endTime, bool $isFullDay, ?string $remarks): void
    {
        $this->id = $id;
        $this->employee_id = $employeeId;
        $this->date = $date;
        $this->reason_id = $reasonId;
        $this->start_time = $startTime;
        $this->end_time = $endTime;
        $this->is_full_day = $isFullDay;
        $this->remarks = $remarks;
    }
}
