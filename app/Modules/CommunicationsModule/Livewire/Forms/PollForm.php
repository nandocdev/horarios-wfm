<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire\Forms;

use App\Modules\CommunicationsModule\DTOs\PollDTO;
use App\Modules\CommunicationsModule\Models\Poll;
use Livewire\Form;

/**
 * Formulario para el manejo de encuestas.
 */
class PollForm extends Form
{
    public ?Poll $pollModel = null;

    public string $question = '';

    /** @var array */
    public array $options = [
        ['text' => '', 'color' => 'blue'],
        ['text' => '', 'color' => 'green'],
    ];

    public string $expires_at = '';

    public ?string $scheduled_at = null;

    public ?string $archive_at = null;

    public string $workflow_action = 'save_draft';

    public bool $is_active = true;

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'question' => 'required|string|max:255',
            'options' => 'required|array|min:2',
            'options.*.text' => 'required|string|max:100',
            'options.*.color' => 'required|string|in:blue,green,red,yellow,gray,indigo,purple,pink',
            'expires_at' => 'required|date|after:now',
            'scheduled_at' => 'nullable|date',
            'archive_at' => 'nullable|date|after:scheduled_at',
            'workflow_action' => 'required|string|in:save_draft,submit_review',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Carga datos del modelo al formulario.
     */
    public function setPoll(Poll $poll): void
    {
        $this->pollModel = $poll;
        $this->question = $poll->question;
        $this->options = $poll->options;
        $this->expires_at = $poll->expires_at->format('Y-m-d\TH:i');
        $this->scheduled_at = $poll->scheduled_at?->format('Y-m-d\TH:i');
        $this->archive_at = $poll->archive_at?->format('Y-m-d\TH:i');
        $this->workflow_action = 'save_draft';
        $this->is_active = (bool) $poll->is_active;
    }

    /**
     * Agrega una nueva opción.
     */
    public function addOption(): void
    {
        if (count($this->options) < 10) {
            $this->options[] = ['text' => '', 'color' => 'blue'];
        }
    }

    /**
     * Elimina una opción.
     */
    public function removeOption(int $index): void
    {
        if (count($this->options) > 2) {
            unset($this->options[$index]);
            $this->options = array_values($this->options);
        }
    }

    /**
     * Convierte el formulario a un DTO inmutable.
     */
    public function toDTO(): PollDTO
    {
        return new PollDTO(
            question: $this->question,
            options: $this->options,
            is_active: $this->is_active,
            expires_at: $this->expires_at,
            scheduled_at: $this->scheduled_at,
            archive_at: $this->archive_at
        );
    }
}
