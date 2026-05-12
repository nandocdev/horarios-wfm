<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CreateCallRecordForm extends Form
{
    #[Validate(['required', 'integer', 'exists:call_queues,id'])]
    public int $queue_id = 0;

    #[Validate(['required', 'string', 'exists:channels,id'])]
    public string $channel_id = '';

    #[Validate(['required', 'string', 'min:6', 'max:20'])]
    public string $phone_number = '';

    #[Validate(['nullable', 'string', 'regex:/^[A-Z0-9-]{6,15}$/'])]
    public string $citizen_identifier = '';

    #[Validate(['required', 'integer', 'exists:case_subtypes,id'])]
    public int $case_subtype_id = 0;

    #[Validate(['required', 'string', 'min:10', 'max:500'])]
    public string $description = '';

    #[Validate(['required', 'string', 'in:pending_operator,open,closed'])]
    public string $status = 'open';
}
