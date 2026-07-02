<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Http\Requests;

use App\Src\Connect\Infrastructure\Persistence\EloquentCallRecord;
use Illuminate\Foundation\Http\FormRequest;

class CreateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EloquentCallRecord::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'queue_id' => ['required', 'integer', 'exists:call_queues,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'phone_number' => ['required', 'string'],
            'citizen_identifier' => ['nullable', 'regex:/^\d{8,12}$/'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
