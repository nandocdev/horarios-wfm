<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\Forms;

use App\Modules\WfmModule\Models\AgentState;
use Livewire\Attributes\Rule;
use Livewire\Form;

class AgentStateForm extends Form
{
    public ?AgentState $agentState = null;

    #[Rule('required|string|max:50')]
    public string $external_code = '';

    #[Rule('required|string|max:100')]
    public string $display_name = '';

    public bool $is_productive = false;

    #[Rule('required|string|max:7')]
    public string $color_hex = '#cbd5e1';

    public function setAgentState(AgentState $model): void
    {
        $this->agentState = $model;
        $this->external_code = $model->external_code;
        $this->display_name = $model->display_name;
        $this->is_productive = $model->is_productive;
        $this->color_hex = $model->color_hex ?? '#cbd5e1';
    }

    public function resetForm(): void
    {
        $this->reset(['external_code', 'display_name', 'is_productive', 'color_hex', 'agentState']);
    }
}
