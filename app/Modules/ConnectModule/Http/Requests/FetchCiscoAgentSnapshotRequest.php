<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Http\Requests;

use App\Modules\ConnectModule\Models\CallRecord;
use Illuminate\Foundation\Http\FormRequest;

class FetchCiscoAgentSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', CallRecord::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'username' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.-]+$/'],
        ];
    }
}
