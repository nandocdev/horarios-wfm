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
            'name' => ['required', 'string', 'max:255'],
            'position_code' => ['required', 'string', 'max:20', $uniqueRule],
            'description' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'is_active' => ['boolean'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'position_code' => 'código de posición',
            'description' => 'descripción',
            'department_id' => 'departamento',
            'is_active' => 'estado activo',
            'salary' => 'salario',
        ];
    }
}
