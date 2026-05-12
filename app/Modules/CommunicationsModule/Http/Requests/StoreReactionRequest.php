<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Requests;

use App\Modules\CommunicationsModule\Enums\ReactionType;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida y autoriza la creación de una reacción.
 */
class StoreReactionRequest extends FormRequest
{
    /**
     * Autorización basada en permisos.
     */
    public function authorize(): bool
    {
        return $this->user()->can('react_to_shoutouts');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.implode(',', array_column(ReactionType::cases(), 'value'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'El tipo de reacción es obligatorio.',
            'type.in' => 'El tipo de reacción especificado no es válido.',
        ];
    }
}
