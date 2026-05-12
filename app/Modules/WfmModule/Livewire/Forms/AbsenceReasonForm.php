<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\Forms;

use App\Modules\WfmModule\Models\AbsenceReasonCode;
use Livewire\Attributes\Rule;
use Livewire\Form;

class AbsenceReasonForm extends Form
{
    public ?AbsenceReasonCode $absenceReasonCode = null;

    #[Rule('required|string|max:100')]
    public string $name = '';

    #[Rule('required|string|max:10')]
    public string $short_code = '';

    public bool $requires_attachment = false;

    public bool $is_excused = true;

    public function setAbsenceReason(AbsenceReasonCode $model): void
    {
        $this->absenceReasonCode = $model;
        $this->name = $model->name;
        $this->short_code = $model->short_code;
        $this->requires_attachment = $model->requires_attachment;
        $this->is_excused = $model->is_excused;
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'short_code', 'requires_attachment', 'is_excused', 'absenceReasonCode']);
    }
}
