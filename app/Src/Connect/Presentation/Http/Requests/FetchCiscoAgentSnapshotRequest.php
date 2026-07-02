<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Http\Requests;

use App\Src\Connect\Infrastructure\Persistence\EloquentCallRecord;
use Illuminate\Foundation\Http\FormRequest;

class FetchCiscoAgentSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', EloquentCallRecord::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'date' => ['nullable', 'date'],
        ];
    }
}
