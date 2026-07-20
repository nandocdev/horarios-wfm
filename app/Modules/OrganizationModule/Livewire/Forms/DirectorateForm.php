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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'is_active' => 'estado activo',
        ];
    }
}
