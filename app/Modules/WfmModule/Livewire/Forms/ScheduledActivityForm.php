<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\Forms;

use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
use Livewire\Attributes\Rule;
use Livewire\Form;

class ScheduledActivityForm extends Form
{
    public ?ScheduledActivityDefinition $definition = null;

    #[Rule('required|string|max:150')]
    public string $name = '';

    #[Rule('required|exists:activity_types,id')]
    public ?int $activity_type_id = null;

    #[Rule('nullable|integer|min:1')]
    public ?int $default_duration_minutes = null;

    #[Rule('nullable|string|max:255')]
    public ?string $default_location = '';

    #[Rule('nullable|string|max:255')]
    public ?string $default_instructor = '';

    public bool $is_active = true;

    public function setDefinition(ScheduledActivityDefinition $model): void
    {
        $this->definition = $model;
        $this->name = $model->name;
        $this->activity_type_id = (int) $model->activity_type_id;
        $this->default_duration_minutes = $model->default_duration_minutes;
        $this->default_location = $model->default_location;
        $this->default_instructor = $model->default_instructor;
        $this->is_active = $model->is_active;
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'activity_type_id', 'default_duration_minutes', 'default_location', 'default_instructor', 'is_active', 'definition']);
    }
}
