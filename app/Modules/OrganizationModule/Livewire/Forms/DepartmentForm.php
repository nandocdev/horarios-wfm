<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire\Forms;

use Livewire\Form;

class DepartmentForm extends Form
{
    public string $name = '';

    public ?string $description = '';

    public int $directorate_id;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'directorate_id' => ['required', 'integer', 'exists:directorates,id'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'directorate_id' => 'dirección',
        ];
    }
}
