<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\EmploymentStatus;

it('deactivates employees when employment status is deactivated', function () {
    $status = EmploymentStatus::create([
        'name' => 'Probation',
        'description' => 'Periodo de prueba',
        'code' => 'probation',
        'is_active' => true,
    ]);

    $employee1 = Employee::factory()->create([
        'employment_status_id' => $status->id,
        'is_active' => true,
    ]);

    $employee2 = Employee::factory()->create([
        'employment_status_id' => $status->id,
        'is_active' => true,
    ]);

    $status->update(['is_active' => false]);

    expect($employee1->fresh()->is_active)->toBeFalse();
    expect($employee2->fresh()->is_active)->toBeFalse();
});
