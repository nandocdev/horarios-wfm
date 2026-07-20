<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire\Forms;

use Livewire\Form;

final class CriteriaFormData extends Form
{
    public string $name = '';

    public ?string $description = null;

    public int $max_score = 100;

    public string $type = 'evaluable';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_score' => ['required', 'integer', 'min:1', 'max:100'],
            'type' => ['required', 'string', 'in:evaluable,red_flag'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'max_score' => 'puntaje máximo',
            'type' => 'tipo',
        ];
    }
}
