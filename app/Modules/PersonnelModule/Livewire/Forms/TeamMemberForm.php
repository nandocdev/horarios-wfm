<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire\Forms;

use Livewire\Form;

class TeamMemberForm extends Form
{
    public int $employee_id = 0;

    public string $start_date = '';

    public ?string $end_date = null;

    public string $remove_end_date = '';

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }

    public function removeRules(): array
    {
        return [
            'remove_end_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'employee_id' => 'empleado',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
            'remove_end_date' => 'fecha de fin',
        ];
    }

    public function resetForAssign(): void
    {
        $this->reset();
        $this->start_date = now()->format('Y-m-d');
    }

    public function resetForRemove(): void
    {
        $this->reset();
        $this->remove_end_date = now()->format('Y-m-d');
    }
}
