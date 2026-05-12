<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\Forms;

use App\Modules\WfmModule\Models\ActivityType;
use Livewire\Attributes\Rule;
use Livewire\Form;

class ActivityTypeForm extends Form
{
    public ?ActivityType $activityType = null;

    #[Rule('required|string|max:50')]
    public string $name = '';

    #[Rule('nullable|string|max:20')]
    public string $color = '#cbd5e1';

    public bool $is_productive = false;

    public bool $is_paid = true;

    public function setActivityType(ActivityType $activityType): void
    {
        $this->activityType = $activityType;
        $this->name = $activityType->name;
        $this->color = $activityType->color ?? '#cbd5e1';
        $this->is_productive = $activityType->is_productive;
        $this->is_paid = $activityType->is_paid;
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'color', 'is_productive', 'is_paid', 'activityType']);
    }
}
