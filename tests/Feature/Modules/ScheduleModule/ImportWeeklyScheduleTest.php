<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Livewire\ImportWeeklySchedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('requires schedules.manage permission to access import component', function () {
    $weekly = WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);

    $user = User::factory()->create();

    // Sin permisos, debe fallar con 403
    $this->actingAs($user);
    
    Livewire::test(ImportWeeklySchedule::class, ['week' => $weekly])
        ->assertForbidden();
});

it('renders import component when user has schedules.manage permission', function () {
    $weekly = WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('schedules.manage');

    $this->actingAs($user);

    Livewire::test(ImportWeeklySchedule::class, ['week' => $weekly])
        ->assertOk()
        ->assertViewIs('wfm::livewire.import-weekly-schedule');
});

it('allows uploading a valid csv file and maps it to importedData', function () {
    Storage::fake('local');

    $weekly = WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('schedules.manage');

    $csvContent = "usuario,centro,jornada,horario,almuerzo,descanso\n";
    $csvContent .= "jdoe,WEB,8 Hours,08:00,12:00,10:00\n";

    $file = UploadedFile::fake()->createWithContent('horarios.csv', $csvContent);

    $this->actingAs($user);

    Livewire::test(ImportWeeklySchedule::class, ['week' => $weekly])
        ->set('csvFile', $file)
        ->assertHasNoErrors()
        ->assertSet('importedData.0.usuario', 'jdoe')
        ->assertSet('importedData.0.jornada', '8 Hours');
});

it('replicates imported schedules to team leaders and sets team assignments to 9 hours', function () {
    Storage::fake('local');

    $weekly = WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);

    // Crear un turno base que coincida con el CSV
    $schedule = \App\Modules\WfmModule\Models\Schedule::create([
        'name' => '8 Hours',
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'total_minutes' => 480,
    ]);

    // Crear directorate, department y position validos
    $directorateId = \Illuminate\Support\Facades\DB::table('directorates')->insertGetId([
        'name' => 'Test Directorate',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $departmentId = \Illuminate\Support\Facades\DB::table('departments')->insertGetId([
        'directorate_id' => $directorateId,
        'name' => 'Test Department',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $positionId = \Illuminate\Support\Facades\DB::table('positions')->insertGetId([
        'department_id' => $departmentId,
        'name' => 'Operador Asist. Serv. Aseg. II',
        'position_code' => 'TEST_CODE',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Crear supervisor y coordinador (lideres) con la posicion
    $supervisor = \App\Modules\PersonnelModule\Models\Employee::factory()->create(['position_id' => $positionId]);
    $coordinator = \App\Modules\PersonnelModule\Models\Employee::factory()->create(['position_id' => $positionId]);

    // Crear equipo asignando al supervisor
    $team = \App\Modules\PersonnelModule\Models\Team::factory()->create([
        'supervisor_id' => $supervisor->id,
    ]);

    // Asociar supervisor y coordinador al equipo
    $supervisor->update(['team_id' => $team->id]);
    $coordinator->update(['team_id' => $team->id]);

    // Definir al coordinador (parent_id de algun empleado)
    $agent = \App\Modules\PersonnelModule\Models\Employee::factory()->create([
        'username' => 'jdoe',
        'email' => 'jdoe@example.com',
        'team_id' => $team->id,
        'position_id' => $positionId,
        'parent_id' => $coordinator->id, // Esto lo convierte en coordinador segun la consulta del seeder
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('schedules.manage');

    $csvContent = "usuario,centro,jornada,horario,almuerzo,descanso\n";
    $csvContent .= "jdoe,WEB,8 Hours,08:00,12:00,10:00\n";

    $file = UploadedFile::fake()->createWithContent('horarios.csv', $csvContent);

    $this->actingAs($user);

    // Ejecutar componente e importar
    Livewire::test(ImportWeeklySchedule::class, ['week' => $weekly])
        ->set('csvFile', $file)
        ->set('importSelectedDays', [1]) // Lunes
        ->call('applyImport')
        ->assertHasNoErrors();

    $today = now()->format('Y-m-d');

    // Verificar que el agente tiene su turno normal de 8 horas (08:00 a 16:00)
    $this->assertDatabaseHas('weekly_schedule_assignments', [
        'weekly_schedule_id' => $weekly->id,
        'employee_id' => $agent->id,
        'day_of_week' => 1,
        'start_time' => $today . ' 08:00:00',
        'end_time' => $today . ' 16:00:00', // 8 horas
    ]);

    // Verificar que el supervisor (lider) recibio la replica con duracion de 9 horas (08:00 a 17:00)
    $this->assertDatabaseHas('weekly_schedule_assignments', [
        'weekly_schedule_id' => $weekly->id,
        'employee_id' => $supervisor->id,
        'day_of_week' => 1,
        'start_time' => $today . ' 08:00:00',
        'end_time' => $today . ' 17:00:00', // 9 horas
    ]);

    // Verificar que el coordinador (lider) recibio la replica con duracion de 9 horas (08:00 a 17:00)
    $this->assertDatabaseHas('weekly_schedule_assignments', [
        'weekly_schedule_id' => $weekly->id,
        'employee_id' => $coordinator->id,
        'day_of_week' => 1,
        'start_time' => $today . ' 08:00:00',
        'end_time' => $today . ' 17:00:00', // 9 horas
    ]);

    // Verificar que el WeeklyTeamAssignment tambien tenga una duracion de 9 horas (08:00 a 17:00)
    $this->assertDatabaseHas('weekly_team_assignments', [
        'weekly_schedule_id' => $weekly->id,
        'team_id' => $team->id,
        'day_of_week' => 1,
        'start_time' => '08:00',
        'end_time' => '17:00', // 9 horas
    ]);
});
