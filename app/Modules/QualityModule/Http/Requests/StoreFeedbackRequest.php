<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('quality.feedback.create');
    }

    public function rules(): array
    {
        return [
            'evaluation_id' => ['required', 'string', 'exists:quality_evaluations,id'],
            'obsfeed' => ['required', 'string', 'max:2500'],
        ];
    }
}
