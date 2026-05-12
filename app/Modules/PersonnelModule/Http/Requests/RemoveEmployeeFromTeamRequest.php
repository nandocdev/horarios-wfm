<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Http\Requests;

use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida y autoriza la remoción de un empleado de un equipo.
 */
class RemoveEmployeeFromTeamRequest extends FormRequest
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
            'end_date' => ['required', 'date', 'after_or_equal:today'],
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
            'end_date' => 'fecha de fin',
        ];
    }
}
