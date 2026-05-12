<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida y autoriza la creación de un comentario.
 */
class StoreCommentRequest extends FormRequest
{
    /**
     * Autorización basada en permisos.
     */
    public function authorize(): bool
    {
        return $this->user()->can('comment_on_news');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'El contenido del comentario es obligatorio.',
            'content.max' => 'El comentario no puede tener más de 1000 caracteres.',
            'parent_id.exists' => 'El comentario padre especificado no existe.',
        ];
    }
}
