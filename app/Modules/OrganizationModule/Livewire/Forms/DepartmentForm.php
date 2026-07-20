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
            'form.name' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'form.directorate_id' => ['required', 'integer', 'exists:directorates,id'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'form.name' => 'nombre',
            'form.description' => 'descripción',
            'form.directorate_id' => 'dirección',
        ];
    }
}
