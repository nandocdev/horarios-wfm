<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'citizen_identifier' => ['required', 'regex:/^\d{8,12}$/'],
            'case_subtype_id' => ['required', 'integer', 'exists:case_subtypes,id'],
            'description' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'citizen_identifier.regex' => 'Cédula debe tener 8-12 dígitos.',
            'description.min' => 'Descripción debe tener al menos 10 caracteres.',
        ];
    }
}
