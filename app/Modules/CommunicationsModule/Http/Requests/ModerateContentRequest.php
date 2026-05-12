<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Requests;

use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Modules\CommunicationsModule\Policies\ContentModerationPolicy;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida y autoriza operaciones de moderación de contenido.
 */
class ModerateContentRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta acción.
     */
    public function authorize(): bool
    {
        $policy = app(ContentModerationPolicy::class);

        return match ($this->input('action')) {
            'approve' => $policy->approve($this->user()),
            'reject' => $policy->reject($this->user()),
            'archive' => $policy->archive($this->user()),
            'submit_for_review' => true, // Cualquier usuario puede enviar a revisión
            default => false,
        };
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:approve,reject,archive,submit_for_review'],
            'content_type' => ['required', 'string', 'in:news,poll,shoutout'],
            'content_id' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'La acción de moderación es requerida.',
            'action.in' => 'La acción debe ser una de: approve, reject, archive, submit_for_review.',
            'content_type.required' => 'El tipo de contenido es requerido.',
            'content_type.in' => 'El tipo de contenido debe ser news, poll o shoutout.',
            'content_id.required' => 'El ID del contenido es requerido.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
        ];
    }

    /**
     * Obtiene el modelo de contenido basado en el tipo y ID.
     */
    public function getContent(bool $asResource = false)
    {
        return match ($this->input('content_type')) {
            'news' => News::findOrFail($this->input('content_id')),
            'poll' => Poll::findOrFail($this->input('content_id')),
            'shoutout' => Shoutout::findOrFail($this->input('content_id')),
        };
    }
}
