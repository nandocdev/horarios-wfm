<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCriteriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('quality.criteria.create');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('quality_criteria', 'code')->ignore($this->route('criteria'))],
            'criterio_text' => ['required', 'string', 'max:500'],
            'puntaje' => ['required', 'integer', 'min:1', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
