<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalibrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('quality.calibrations.create');
    }

    public function rules(): array
    {
        return [
            'evaluation_id' => ['required', 'string', 'exists:quality_evaluations,id'],
            'score_nuevo' => ['required', 'integer', 'min:0', 'max:100'],
            'obs' => ['nullable', 'string', 'max:2500'],
        ];
    }
}
