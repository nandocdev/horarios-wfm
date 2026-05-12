<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CaseSubtypeForm extends Form
{
    #[Validate(['required', 'integer', 'exists:call_queues,id'])]
    public int $queue_id = 0;

    #[Validate(['required', 'string', 'max:100'])]
    public string $code = '';

    #[Validate(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Validate(['nullable', 'string', 'max:500'])]
    public ?string $description = null;

    #[Validate(['boolean'])]
    public bool $is_active = true;
}
