<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Livewire\Forms;

use Livewire\Form;

class DirectorateForm extends Form
{
    public string $name = '';

    public ?string $description = '';

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'form.is_active' => ['boolean'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'form.name' => 'nombre',
            'form.description' => 'descripción',
            'form.is_active' => 'estado activo',
        ];
    }
}
