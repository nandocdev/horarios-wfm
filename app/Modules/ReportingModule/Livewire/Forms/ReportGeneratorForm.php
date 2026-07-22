<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Livewire\Forms;

use Livewire\Attributes\Rule;
use Livewire\Form;

class ReportGeneratorForm extends Form
{
    #[Rule('required|date')]
    public string $dateFrom = '';

    #[Rule('required|date|after_or_equal:dateFrom')]
    public string $dateTo = '';

    #[Rule('nullable|exists:teams,id')]
    public ?int $teamId = null;

    #[Rule('nullable|exists:employees,id')]
    public ?int $employeeId = null;

    #[Rule('nullable|exists:call_queues,id')]
    public ?int $queueId = null;

    #[Rule('nullable|in:daily,weekly,monthly')]
    public string $interval = 'daily';
}
