<?php

declare(strict_types=1);

use App\Modules\OrganizationModule\Actions\CreateDepartmentAction;
use App\Modules\OrganizationModule\Actions\CreateDirectorateAction;
use App\Modules\OrganizationModule\Actions\CreatePositionAction;
use App\Modules\OrganizationModule\Actions\TogglePositionStatusAction;
use App\Modules\OrganizationModule\Actions\UpdatePositionAction;
use App\Modules\OrganizationModule\DTOs\DepartmentDTO;
use App\Modules\OrganizationModule\DTOs\DirectorateDTO;
use App\Modules\OrganizationModule\DTOs\PositionDTO;
use App\Modules\OrganizationModule\Models\Position;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Dir Posiciones'])
    );
    $this->department = (new CreateDepartmentAction)->execute(
        DepartmentDTO::fromArray([
            'directorate_id' => $this->directorate->id,
            'name' => 'Depto Posiciones',
        ])
    );
});

test('crea una posicion exitosamente', function () {
    $position = (new CreatePositionAction)->execute(
        PositionDTO::fromArray([
            'department_id' => $this->department->id,
            'name' => 'Analista de Prueba',
            'position_code' => 'AN-001',
            'description' => 'Posición de prueba',
            'is_active' => true,
        ])
    );

    expect($position)->toBeInstanceOf(Position::class);
    expect($position->name)->toBe('Analista de Prueba');
    expect($position->position_code)->toBe('AN-001');
    expect($position->is_active)->toBeTrue();
});

test('crea una posicion con salario', function () {
    $position = (new CreatePositionAction)->execute(
        PositionDTO::fromArray([
            'department_id' => $this->department->id,
            'name' => 'Analista Salario',
            'position_code' => 'AN-002',
            'salary' => 1500.50,
        ])
    );

    expect((float) $position->salary)->toBe(1500.50);
});

test('valida que el departamento existe al crear posicion', function () {
    expect(fn () => (new CreatePositionAction)->execute(
        PositionDTO::fromArray([
            'department_id' => 99999,
            'name' => 'Posición Inexistente',
            'position_code' => 'IN-001',
        ])
    ))->toThrow(ModelNotFoundException::class);
});

test('no permite codigos de posicion duplicados', function () {
    (new CreatePositionAction)->execute(
        PositionDTO::fromArray([
            'department_id' => $this->department->id,
            'name' => 'Posición Única',
            'position_code' => 'UN-001',
        ])
    );

    expect(fn () => (new CreatePositionAction)->execute(
        PositionDTO::fromArray([
            'department_id' => $this->department->id,
            'name' => 'Otra Posición',
            'position_code' => 'UN-001',
        ])
    ))->toThrow(QueryException::class);
});

test('actualiza una posicion exitosamente', function () {
    $position = (new CreatePositionAction)->execute(
        PositionDTO::fromArray([
            'department_id' => $this->department->id,
            'name' => 'Original',
            'position_code' => 'OR-001',
        ])
    );

    $updated = (new UpdatePositionAction)->execute(
        $position,
        PositionDTO::fromArray([
            'department_id' => $this->department->id,
            'name' => 'Actualizada',
            'position_code' => 'OR-001',
            'description' => 'Nueva descripción',
            'is_active' => false,
            'salary' => 2000.00,
        ])
    );

    expect($updated->name)->toBe('Actualizada');
    expect($updated->is_active)->toBeFalse();
    expect((float) $updated->salary)->toBe(2000.00);
});

test('cambia el estado de una posicion', function () {
    $position = (new CreatePositionAction)->execute(
        PositionDTO::fromArray([
            'department_id' => $this->department->id,
            'name' => 'Toggle Position',
            'position_code' => 'TG-001',
        ])
    );

    expect($position->is_active)->toBeTrue();

    $toggled = (new TogglePositionStatusAction)->execute($position);
    expect($toggled->is_active)->toBeFalse();

    $toggledAgain = (new TogglePositionStatusAction)->execute($toggled);
    expect($toggledAgain->is_active)->toBeTrue();
});

test('mantiene relacion jerarquica completa', function () {
    $position = (new CreatePositionAction)->execute(
        PositionDTO::fromArray([
            'department_id' => $this->department->id,
            'name' => 'Posición Jerarquía',
            'position_code' => 'JH-001',
        ])
    );

    $position->load('department.directorate');

    expect($position->department->id)->toBe($this->department->id);
    expect($position->department->directorate->id)->toBe($this->directorate->id);
});

test('permite soft delete en posiciones', function () {
    $position = (new CreatePositionAction)->execute(
        PositionDTO::fromArray([
            'department_id' => $this->department->id,
            'name' => 'Position Soft',
            'position_code' => 'SF-001',
        ])
    );

    $position->delete();

    expect(Position::find($position->id))->toBeNull();
    expect(Position::withTrashed()->find($position->id))->not->toBeNull();
});
