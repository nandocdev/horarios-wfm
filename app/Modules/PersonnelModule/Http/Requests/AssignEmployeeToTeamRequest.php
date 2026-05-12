<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Http\Requests;

use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida y autoriza la asignación de un empleado a un equipo.
 */
class AssignEmployeeToTeamRequest extends FormRequest
{
    /**
     * Autorización basada en Policy del equipo.
     */
    public function authorize(): bool
    {
        $team = Team::find($this->input('team_id'));

        return $team && $this->user()->can('update', $team);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'empleado',
            'team_id' => 'equipo',
            'start_date' => 'fecha de inicio',
            'end_date' => 'fecha de fin',
        ];
    }
}
