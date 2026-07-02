<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [];
    }
}
