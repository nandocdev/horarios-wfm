<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('quality.evaluations.create');
    }

    public function rules(): array
    {
        return [
            'queue_id' => ['required', 'string', 'exists:quality_queues,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'clip_id' => ['nullable', 'integer', 'exists:processed_clips,id'],
            'dtcall' => ['nullable', 'date', 'before_or_equal:today'],
            'tmcall' => ['nullable', 'date_format:H:i', 'after:06:00', 'before:19:00'],
            'callobs' => ['nullable', 'string', 'max:2500'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.criteria_version_id' => ['required', 'string', 'exists:quality_criteria_versions,id'],
            'scores.*.puntaje' => ['required', 'integer', 'min:0'],
            'red_flags' => ['nullable', 'array'],
            'red_flags.*.red_flag_criteria_id' => ['required_with:red_flags', 'string', 'exists:quality_red_flag_criteria,id'],
        ];
    }
}
