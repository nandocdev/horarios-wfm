<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\Forms;

use Livewire\Form;

final class LeaveRequestForm extends Form
{
    public string $type = 'cuatrimestral';

    public string $date = '';

    public ?string $startTime = '';

    public ?string $endTime = '';

    public string $reason = '';

    public bool $isFullDay = false;

    /**
     * Reglas de validación dinámicas basadas en el estado del formulario.
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date|after_or_equal:today',
            'startTime' => 'required_if:isFullDay,false',
            'endTime' => 'required_if:isFullDay,false',
            'reason' => 'required|string|min:10',
        ];
    }

    /**
     * Nombres de atributos personalizados para mensajes de error.
     */
    public function validationAttributes(): array
    {
        return [
            'date' => 'fecha',
            'startTime' => 'hora de inicio',
            'endTime' => 'hora de fin',
            'reason' => 'motivo',
        ];
    }
}
