<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Actions\CreateDepartmentAction;
use App\Modules\PersonnelModule\Actions\CreateDirectorateAction;
use App\Modules\PersonnelModule\Actions\CreateTeamAction;
use App\Modules\PersonnelModule\DTOs\DepartmentDTO;
use App\Modules\PersonnelModule\DTOs\DirectorateDTO;
use App\Modules\PersonnelModule\DTOs\TeamDTO;
use App\Modules\PersonnelModule\Models\Directorate;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Database\QueryException;

beforeEach(function () {
    // No requiere seeder específico de roles para validación de flujo core
});

it('crea directorate, department y team con relaciones jerarquicas', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray([
            'name' => 'Dirección de Operaciones',
            'description' => 'Dirección foco en operaciones',
            'is_active' => true,
        ])
    );

    $department = (new CreateDepartmentAction)->execute(
        DepartmentDTO::fromArray([
            'directorate_id' => $directorate->id,
            'name' => 'Departamento de Turnos',
            'description' => 'Gestiona turnos y horarios',
        ])
    );

    $team = (new CreateTeamAction)->execute(
        TeamDTO::fromArray([
            'name' => 'Equipo Alpha',
            'description' => 'Equipo inicial de turno',
            'supervisor_id' => null,
            'is_active' => true,
        ])
    );

    expect(Directorate::with('departments')->find($directorate->id))->not->toBeNull();
    expect($directorate->departments->first()->id)->toBe($department->id);
    expect(Team::find($team->id))->not->toBeNull();
});

it('evita duplicacion de nombres en directorates por validacion y constraint', function () {
    $data = [
        'name' => 'Dirección Legal',
        'description' => 'Legal y Compliance',
        'is_active' => true,
    ];

    (new CreateDirectorateAction)->execute(DirectorateDTO::fromArray($data));

    $this->expectException(QueryException::class);

    (new CreateDirectorateAction)->execute(DirectorateDTO::fromArray($data));
});
