<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire\Forms;

use Livewire\Form;

final class QueueFormData extends Form
{
    public string $name = '';

    public string $code = '';

    public ?string $description = null;

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:quality_queues,code'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'code' => 'código',
            'description' => 'descripción',
            'is_active' => 'estado activo',
        ];
    }
}
