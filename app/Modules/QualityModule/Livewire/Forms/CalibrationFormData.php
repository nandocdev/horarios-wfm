<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire\Forms;

use Livewire\Form;

final class CalibrationFormData extends Form
{
    public int $score_nuevo = 0;

    public ?string $obs = null;

    public function rules(): array
    {
        return [
            'score_nuevo' => ['required', 'integer', 'min:0', 'max:100'],
            'obs' => ['nullable', 'string', 'max:2500'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'score_nuevo' => 'nuevo score',
            'obs' => 'observación',
        ];
    }
}
