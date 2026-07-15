<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Livewire\Forms;

use Livewire\Form;

final class EvaluationFormData extends Form
{
    public string $queue_id = '';

    public int $employee_id = 0;

    public ?int $clip_id = null;

    public ?string $dtcall = null;

    public ?string $tmcall = null;

    public ?string $callobs = null;

    /** @var array<array{criteria_version_id: string, cumple: bool}> */
    public array $scores = [];

    /** @var array<int, bool> */
    public array $red_flags = [];

    public function rules(): array
    {
        return [
            'queue_id' => 'required|string|exists:quality_queues,id',
            'employee_id' => 'required|integer|exists:employees,id',
            'clip_id' => 'nullable|integer|exists:processed_clips,id',
            'dtcall' => 'nullable|date|before_or_equal:today',
            'tmcall' => 'nullable|date_format:H:i',
            'callobs' => 'nullable|string|max:2500',
            'scores' => 'required|array|min:1',
            'red_flags' => 'nullable|array',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'queue_id' => 'cola',
            'employee_id' => 'empleado',
            'dtcall' => 'fecha de llamada',
            'tmcall' => 'hora de llamada',
            'callobs' => 'observaciones',
            'scores' => 'puntajes',
        ];
    }
}
