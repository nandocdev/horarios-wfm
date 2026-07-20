<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire\Forms;

use Livewire\Form;

class PositionForm extends Form
{
    public string $name = '';

    public string $position_code = '';

    public ?string $description = '';

    public int $department_id;

    public bool $is_active = true;

    /** @var string|int|null */
    public $salary = null;

    public function rules(): array
    {
        $uniqueRule = 'unique:positions,position_code';

        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.position_code' => ['required', 'string', 'max:20', $uniqueRule],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'form.department_id' => ['required', 'integer', 'exists:departments,id'],
            'form.is_active' => ['boolean'],
            'form.salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'form.name' => 'nombre',
            'form.position_code' => 'código de posición',
            'form.description' => 'descripción',
            'form.department_id' => 'departamento',
            'form.is_active' => 'estado activo',
            'form.salary' => 'salario',
        ];
    }
}
