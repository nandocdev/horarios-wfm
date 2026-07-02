<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Http\Requests;

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
            'talk_time' => ['required', 'integer', 'min:0'],
            'handle_time' => ['required', 'integer', 'min:0'],
            'contact_disposition' => ['required', 'integer', 'in:1,2,3'],
        ];
    }
}
