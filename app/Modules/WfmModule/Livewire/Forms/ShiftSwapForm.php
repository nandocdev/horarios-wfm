<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\Forms;

use Livewire\Attributes\Rule;
use Livewire\Form;

class ShiftSwapForm extends Form
{
    #[Rule('required|date|after:today')]
    public string $requestedDate = '';

    #[Rule('nullable|date|after_or_equal:requestedDate')]
    public ?string $endDate = null;

    #[Rule('required|exists:employees,id')]
    public int $recipientId = 0;

    #[Rule('nullable|string|max:255')]
    public ?string $reason = null;
}
