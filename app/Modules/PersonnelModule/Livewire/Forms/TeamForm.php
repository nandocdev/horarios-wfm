<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire\Forms;

use Livewire\Form;

class TeamForm extends Form
{
    public string $name = '';

    public ?string $description = null;

    public ?int $supervisor_id = null;

    public ?string $cisco_team_id = null;

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:teams,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'cisco_team_id' => ['nullable', 'string', 'max:255', 'unique:teams,cisco_team_id'],
            'is_active' => ['boolean'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'supervisor_id' => 'supervisor',
            'cisco_team_id' => 'ID de equipo Cisco',
            'is_active' => 'estado activo',
        ];
    }
}
