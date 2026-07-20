<?php

declare(strict_types=1);

use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use App\Modules\OrganizationModule\Models\Position;

test('directorate usa SoftDeletes', function () {
    expect(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(Directorate::class)))->toBeTrue();
});

test('department usa SoftDeletes', function () {
    expect(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(Department::class)))->toBeTrue();
});

test('position usa SoftDeletes', function () {
    expect(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(Position::class)))->toBeTrue();
});

test('department tiene is_active en fillable', function () {
    $department = new Department;
    expect(in_array('is_active', $department->getFillable()))->toBeTrue();
});

test('department tiene is_active como boolean cast', function () {
    $department = new Department;
    $casts = $department->getCasts();
    expect($casts['is_active'] ?? null)->toBe('boolean');
});

test('no existen relaciones directas a Employees en OrganizationModule', function () {
    $departmentMethods = collect((new ReflectionClass(Department::class))->getMethods())
        ->map(fn ($m) => $m->getName())
        ->toArray();

    $positionMethods = collect((new ReflectionClass(Position::class))->getMethods())
        ->map(fn ($m) => $m->getName())
        ->toArray();

    expect(in_array('employees', $departmentMethods))->toBeFalse('Department no debe tener método employees()');
    expect(in_array('employees', $positionMethods))->toBeFalse('Position no debe tener método employees()');
    expect(in_array('users', $positionMethods))->toBeFalse('Position no debe tener método users()');
});

test('directorate model usa Auditable trait', function () {
    $traits = class_uses(Directorate::class);
    expect(in_array('App\Modules\CoreModule\Concerns\Auditable', $traits))->toBeTrue();
});

test('department model usa Auditable trait', function () {
    $traits = class_uses(Department::class);
    expect(in_array('App\Modules\CoreModule\Concerns\Auditable', $traits))->toBeTrue();
});

test('position model usa Auditable trait', function () {
    $traits = class_uses(Position::class);
    expect(in_array('App\Modules\CoreModule\Concerns\Auditable', $traits))->toBeTrue();
});
