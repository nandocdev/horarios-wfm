<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire\Forms;

use Livewire\Form;

final class FeedbackFormData extends Form
{
    public string $obsfeed = '';

    public function rules(): array
    {
        return [
            'obsfeed' => ['required', 'string', 'max:2500'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'obsfeed' => 'observaciones',
        ];
    }
}
