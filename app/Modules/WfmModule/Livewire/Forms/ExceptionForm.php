<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\Forms;

use App\Modules\WfmModule\Models\ScheduleException;
use Livewire\Attributes\Rule;
use Livewire\Form;

class ExceptionForm extends Form
{
    #[Rule('required|exists:employees,id')]
    public ?int $employee_id = null;

    #[Rule('required|exists:absence_reason_codes,id')]
    public ?int $absence_reason_code_id = null;

    #[Rule('required|date')]
    public $start_at;

    #[Rule('required|date|after_or_equal:start_at')]
    public $end_at;

    #[Rule('boolean')]
    public bool $is_full_day = true;

    #[Rule('nullable|string|max:500')]
    public ?string $remarks = null;

    public function setException(ScheduleException $exception): void
    {
        $this->employee_id = $exception->employee_id;
        $this->absence_reason_code_id = $exception->absence_reason_code_id;
        $this->start_at = $exception->start_at->format('Y-m-d\TH:i');
        $this->end_at = $exception->end_at->format('Y-m-d\TH:i');
        $this->is_full_day = $exception->is_full_day;
        $this->remarks = $exception->remarks;
    }

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employee_id,
            'absence_reason_code_id' => $this->absence_reason_code_id,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'is_full_day' => $this->is_full_day,
            'remarks' => $this->remarks,
            'created_by' => auth()->id(),
        ];
    }
}
