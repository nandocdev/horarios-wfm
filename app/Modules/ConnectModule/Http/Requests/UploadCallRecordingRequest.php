<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCallRecordingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimetypes:video/*,audio/*', 'max:204800'],
            'agent_call_performance_id' => ['required', 'integer', 'exists:agent_call_performance,id'],
        ];
    }
}
