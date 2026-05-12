<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la actualización de una categoría.
 */
class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($categoryId)],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
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
