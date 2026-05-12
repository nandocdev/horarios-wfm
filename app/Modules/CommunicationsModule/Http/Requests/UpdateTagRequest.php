<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la actualización de un tag.
 */
class UpdateTagRequest extends FormRequest
{
    /**
     * Autorización - requiere permisos de gestión de comunicaciones.
     */
    public function authorize(): bool
    {
        return $this->user()->can('communications.manage');
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        $tagId = $this->route('tag')?->id ?? $this->route('tag');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('tags', 'name')->ignore($tagId)],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('tags', 'slug')->ignore($tagId)],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Preparar datos antes de la validación.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('slug') && $this->has('name')) {
            $this->merge([
                'slug' => str($this->name)->slug()->toString(),
            ]);
        }
    }
}
