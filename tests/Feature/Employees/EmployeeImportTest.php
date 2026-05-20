<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Actions\ImportEmployeesAction;
use App\Modules\PersonnelModule\DTOs\ImportEmployeesDTO;
use App\Modules\PersonnelModule\Livewire\ImportEmployees;
use App\Modules\PersonnelModule\Models\Department;
use App\Modules\PersonnelModule\Models\Directorate;
use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Models\Position;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('queue.default', 'sync');

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['code' => 'ADM', 'hierarchy_level' => 99]);

    foreach (['employees.import', 'employees.view'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }
});

it('imports employees in chunked queue and reports rejected rows', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['force_password_change' => false]);
    $admin->assignRole('admin');
    $admin->givePermissionTo('employees.import');

    $suffix = Str::lower(Str::random(6));

    $directorate = Directorate::firstOrCreate(
        ['name' => 'DIR-IMP-'.$suffix],
        ['description' => 'Import test']
    );

    $department = Department::firstOrCreate(
        ['name' => 'DEP-IMP-'.$suffix, 'directorate_id' => $directorate->id],
        ['description' => 'Import test']
    );

    $position = Position::firstOrCreate(
        ['name' => 'POS-IMP-'.$suffix, 'department_id' => $department->id],
        ['description' => 'Import test', 'is_active' => true, 'position_code' => Str::upper(Str::random(8))]
    );

    $status = EmploymentStatus::firstOrCreate(
        ['code' => 'active-imp-'.$suffix],
        ['name' => 'Active Import '.$suffix, 'is_active' => true]
    );

    $team = Team::factory()->create();

    $csv = implode("\n", [
        'employee_number,username,first_name,last_name,email,position_id,team_id,employment_status_id,hire_date',
        "EMP900001,user900001,Ana,Uno,ana{$suffix}@example.com,{$position->id},{$team->id},{$status->id},2025-01-15",
        "EMP900001,user900002,Ana,Dos,ana2{$suffix}@example.com,{$position->id},{$team->id},{$status->id},2025-01-15",
        "EMP900003,user900003,Ana,Tres,ana3{$suffix}@example.com,{$position->id},999999,{$status->id},2025-01-15",
    ]);

    $path = 'employees/imports/employees-test.csv';
    Storage::disk('local')->put($path, $csv);

    $dto = new ImportEmployeesDTO(
        storedPath: $path,
        originalFilename: 'employees-test.csv',
        createdBy: (int) $admin->id,
        chunkSize: 2,
    );

    $batch = app(ImportEmployeesAction::class)->execute($dto)->fresh();

    expect($batch)->not->toBeNull();
    expect($batch->batch_id)->not->toBeNull();
    expect($batch->total_rows)->toBe(3);
    expect($batch->imported_rows)->toBe(1);
    expect($batch->rejected_rows)->toBe(2);
    expect(in_array($batch->status, ['completed', 'completed_with_errors'], true))->toBeTrue();

    $this->assertDatabaseHas('employees', [
        'employee_number' => 'EMP900001',
        'username' => 'user900001',
    ]);

    $this->assertDatabaseMissing('employees', [
        'username' => 'user900003',
    ]);
});

it('validates livewire import form file type', function () {
    $admin = User::factory()->create(['force_password_change' => false]);
    $admin->assignRole('admin');
    $admin->givePermissionTo('employees.import');

    Livewire::actingAs($admin)
        ->test(ImportEmployees::class)
        ->set('form.csv', UploadedFile::fake()->create('employees.pdf', 10, 'application/pdf'))
        ->call('import')
        ->assertHasErrors(['form.csv']);
});
