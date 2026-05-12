<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ChannelForm extends Form
{
    #[Validate(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Validate(['nullable', 'string', 'max:500'])]
    public ?string $description = null;

    #[Validate(['boolean'])]
    public bool $is_active = true;
}
