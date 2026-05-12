<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire\Forms;

use App\Modules\CommunicationsModule\DTOs\ShoutoutDTO;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Livewire\Form;

/**
 * Formulario para el manejo de reconocimientos (shoutouts).
 */
class ShoutoutForm extends Form
{
    public ?Shoutout $shoutoutModel = null;

    public ?int $employee_id = null;

    public string $message = '';

    public bool $is_active = true;

    public ?string $scheduled_at = null;

    public ?string $archive_at = null;

    /** @var int[] */
    public array $category_ids = [];

    /** @var int[] */
    public array $tag_ids = [];

    public string $workflow_action = 'save_draft';

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'employee_id' => 'required|integer|exists:employees,id',
            'message' => 'required|string|max:500',
            'is_active' => 'boolean',
            'scheduled_at' => 'nullable|date',
            'archive_at' => 'nullable|date|after:scheduled_at',
            'category_ids' => 'array',
            'category_ids.*' => 'integer|exists:categories,id',
            'tag_ids' => 'array',
            'tag_ids.*' => 'integer|exists:tags,id',
            'workflow_action' => 'required|string|in:save_draft,submit_review',
        ];
    }

    /**
     * Carga datos del modelo al formulario.
     */
    public function setShoutout(Shoutout $shoutout): void
    {
        $this->shoutoutModel = $shoutout;
        $this->employee_id = $shoutout->employee_id;
        $this->message = $shoutout->message;
        $this->is_active = (bool) $shoutout->is_active;
        $this->scheduled_at = $shoutout->scheduled_at?->format('Y-m-d\TH:i');
        $this->archive_at = $shoutout->archive_at?->format('Y-m-d\TH:i');
        $this->category_ids = $shoutout->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
        $this->tag_ids = $shoutout->tags()->pluck('tags.id')->map(fn ($id) => (int) $id)->all();
        $this->workflow_action = 'save_draft';
    }

    /**
     * Limpia el formulario.
     */
    public function resetForm(): void
    {
        $this->reset([
            'employee_id',
            'message',
            'is_active',
            'scheduled_at',
            'archive_at',
            'category_ids',
            'tag_ids',
            'workflow_action',
        ]);
        $this->workflow_action = 'save_draft';
        $this->shoutoutModel = null;
    }

    /**
     * Convierte el formulario a un DTO.
     */
    public function toDTO(): ShoutoutDTO
    {
        return new ShoutoutDTO(
            employee_id: (int) $this->employee_id,
            message: $this->message,
            is_active: $this->is_active,
            scheduled_at: $this->scheduled_at,
            archive_at: $this->archive_at,
            category_ids: array_map('intval', $this->category_ids),
            tag_ids: array_map('intval', $this->tag_ids),
            workflow_action: $this->workflow_action
        );
    }
}
