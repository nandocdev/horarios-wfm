<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para crear una encuesta.
 */
class StorePollRequest extends FormRequest
{
    /**
     * Autorización basada en permisos.
     */
    public function authorize(): bool
    {
        return $this->user()->can('polls.manage');
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:500'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*.label' => ['required', 'string', 'max:100'],
            'options.*.value' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'question.required' => 'La pregunta es obligatoria.',
            'options.required' => 'Debe proporcionar al menos 2 opciones.',
            'options.min' => 'Debe proporcionar al menos 2 opciones.',
            'options.max' => 'No puede proporcionar más de 10 opciones.',
            'options.*.label.required' => 'Cada opción debe tener una etiqueta.',
            'options.*.value.required' => 'Cada opción debe tener un valor.',
            'expires_at.after' => 'La fecha de expiración debe ser futura.',
        ];
    }

    /**
     * Preparar datos para validación.
     */
    protected function prepareForValidation(): void
    {
        if ($this->options && is_array($this->options)) {
            $this->merge([
                'options' => array_values($this->options), // Reindexar array
            ]);
        }
    }
}
