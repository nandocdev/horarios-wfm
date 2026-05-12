<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para crear un shoutout.
 */
class StoreShoutoutRequest extends FormRequest
{
    /**
     * Autorización basada en permisos.
     */
    public function authorize(): bool
    {
        return $this->user()->can('shoutouts.manage');
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'message' => ['required', 'string', 'max:500', 'min:10'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Debe seleccionar un empleado.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'message.required' => 'El mensaje es obligatorio.',
            'message.min' => 'El mensaje debe tener al menos 10 caracteres.',
            'message.max' => 'El mensaje no puede superar los 500 caracteres.',
        ];
    }
}
