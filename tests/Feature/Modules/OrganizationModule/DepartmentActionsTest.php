<?php

declare(strict_types=1);

use App\Modules\OrganizationModule\Actions\CreateDepartmentAction;
use App\Modules\OrganizationModule\Actions\CreateDirectorateAction;
use App\Modules\OrganizationModule\Actions\ToggleDepartmentStatusAction;
use App\Modules\OrganizationModule\Actions\UpdateDepartmentAction;
use App\Modules\OrganizationModule\DTOs\DepartmentDTO;
use App\Modules\OrganizationModule\DTOs\DirectorateDTO;
use App\Modules\OrganizationModule\Models\Department;
use Illuminate\Database\Eloquent\ModelNotFoundException;

test('crea un departamento exitosamente', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Dir Dept'])
    );

    $department = (new CreateDepartmentAction)->execute(
        DepartmentDTO::fromArray([
            'directorate_id' => $directorate->id,
            'name' => 'Departamento Test',
            'description' => 'Descripción departamento',
        ])
    );

    expect($department)->toBeInstanceOf(Department::class);
    expect($department->name)->toBe('Departamento Test');
    expect($department->directorate_id)->toBe($directorate->id);
});

test('valida que la direccion existe al crear departamento', function () {
    expect(fn () => (new CreateDepartmentAction)->execute(
        DepartmentDTO::fromArray([
            'directorate_id' => 99999,
            'name' => 'Depto Inexistente',
        ])
    ))->toThrow(ModelNotFoundException::class);
});

test('actualiza un departamento exitosamente', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Dir Original'])
    );
    $newDirectorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Dir Nueva'])
    );

    $department = (new CreateDepartmentAction)->execute(
        DepartmentDTO::fromArray([
            'directorate_id' => $directorate->id,
            'name' => 'Depto Original',
        ])
    );

    $updated = (new UpdateDepartmentAction)->execute(
        $department,
        DepartmentDTO::fromArray([
            'directorate_id' => $newDirectorate->id,
            'name' => 'Depto Actualizado',
            'description' => 'Nueva desc',
        ])
    );

    expect($updated->name)->toBe('Depto Actualizado');
    expect($updated->directorate_id)->toBe($newDirectorate->id);
});

test('cambia el estado de un departamento', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Dir Estado'])
    );
    $department = (new CreateDepartmentAction)->execute(
        DepartmentDTO::fromArray([
            'directorate_id' => $directorate->id,
            'name' => 'Depto Estado',
        ])
    );

    $toggled = (new ToggleDepartmentStatusAction)->execute($department);
    expect($toggled->is_active)->toBeFalse();

    $toggledAgain = (new ToggleDepartmentStatusAction)->execute($toggled);
    expect($toggledAgain->is_active)->toBeTrue();
});

test('mantiene relacion jerarquica con direccion', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Dir Jerarquía'])
    );
    $department = (new CreateDepartmentAction)->execute(
        DepartmentDTO::fromArray([
            'directorate_id' => $directorate->id,
            'name' => 'Depto Hijo',
        ])
    );

    expect($department->directorate->id)->toBe($directorate->id);
    expect($directorate->departments->first()->id)->toBe($department->id);
});

test('permite soft delete en departamentos', function () {
    $directorate = (new CreateDirectorateAction)->execute(
        DirectorateDTO::fromArray(['name' => 'Dir Soft'])
    );
    $department = (new CreateDepartmentAction)->execute(
        DepartmentDTO::fromArray([
            'directorate_id' => $directorate->id,
            'name' => 'Depto Soft',
        ])
    );

    $department->delete();

    expect(Department::find($department->id))->toBeNull();
    expect(Department::withTrashed()->find($department->id))->not->toBeNull();
});
