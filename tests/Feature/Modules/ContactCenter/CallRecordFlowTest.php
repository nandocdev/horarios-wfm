<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Actions\CloseCallRecordAction;
use App\Modules\ConnectModule\Actions\CompleteCallRecordAction;
use App\Modules\ConnectModule\Actions\CreateCallRecordAction;
use App\Modules\ConnectModule\DTOs\CallCloseDTO;
use App\Modules\ConnectModule\DTOs\CallCompleteDTO;
use App\Modules\ConnectModule\DTOs\CallStartDTO;
use App\Modules\ConnectModule\Livewire\CreateCallRecord;
use App\Modules\ConnectModule\Livewire\EditCallRecord;
use App\Modules\ConnectModule\Livewire\ListCallRecords;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Models\CaseSubtype;
use App\Modules\ConnectModule\Models\Channel;
use App\Modules\CoreModule\Models\Permission;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates, completes and closes a call record via actions', function () {
    $channel = Channel::create([
        'id' => Str::ulid(),
        'name' => 'Salud',
        'description' => 'Servicios de Salud',
        'is_active' => true,
    ]);

    $queue = CallQueue::create([
        'name' => 'Servicios de Salud',
        'description' => 'Cola de servicios médicos',
        'is_active' => true,
        'channel_id' => $channel->id,
    ]);

    $startDto = new CallStartDTO(
        ciscoCallId: 'CISCO-20260407-0001',
        queueName: 'Servicios de Salud',
        phoneNumber: '6612-3456',
        ivrStartedAt: now(),
    );

    $callRecord = app(CreateCallRecordAction::class)->execute($startDto);

    expect($callRecord->status)->toBe('pending_operator');
    expect($callRecord->employee_id)->toBeNull();
    expect($callRecord->queue_id)->toBe($queue->id);

    $caseSubtype = CaseSubtype::create([
        'code' => 'MED_CITA_CONTROL',
        'queue_id' => $queue->id,
        'name' => 'Cita de Control',
    ]);

    $completeDto = new CallCompleteDTO(
        citizenIdentifier: '12345678',
        caseSubtypeId: $caseSubtype->id,
        queueId: $queue->id,
        description: 'Cliente solicita cita de control general.',
        employeeId: null,
    );

    $completed = app(CompleteCallRecordAction::class)->execute($callRecord, $completeDto);

    expect($completed->status)->toBe('open');
    expect($completed->citizen_identifier)->toBe('12345678');

    $closeDto = new CallCloseDTO(
        ciscoCallId: 'CISCO-20260407-0001',
        ivrEndedAt: now()->addMinutes(10),
        status: 'closed',
    );

    $closed = app(CloseCallRecordAction::class)->execute($completed, $closeDto);

    expect($closed->status)->toBe('closed');
    expect($closed->duration_minutes)->toBeFloat();
});

it('allows operator to complete a pending call record through livewire', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $role = Role::firstOrCreate(
        ['name' => 'operator', 'guard_name' => 'web'],
        ['code' => 'OP', 'hierarchy_level' => 1],
    );

    $user->assignRole($role);
    Permission::firstOrCreate(['name' => 'call_records.update', 'guard_name' => 'web']);
    $user->givePermissionTo('call_records.update');

    $channel = Channel::create([
        'id' => Str::ulid(),
        'name' => 'Salud',
        'description' => 'Servicios de Salud',
        'is_active' => true,
    ]);

    $queue = CallQueue::create([
        'name' => 'Servicios de Salud',
        'description' => 'Cola de servicios médicos',
        'is_active' => true,
        'channel_id' => $channel->id,
    ]);

    $record = CallRecord::create([
        'cisco_call_id' => 'CISCO-20260407-0002',
        'queue_id' => $queue->id,
        'phone_number' => '6612-3457',
        'ivr_started_at' => now(),
        'status' => 'pending_operator',
    ]);

    $caseSubtype = CaseSubtype::create([
        'code' => 'MED_CITA_CONTROL',
        'queue_id' => $queue->id,
        'name' => 'Cita de Control',
    ]);

    Livewire::actingAs($user)
        ->test(EditCallRecord::class, ['callRecord' => $record])
        ->set('form.citizen_identifier', '87654321')
        ->set('form.case_subtype_id', $caseSubtype->id)
        ->set('form.description', 'Cliente solicita cita urgente.')
        ->call('save');

    $this->assertDatabaseHas('call_records', [
        'id' => $record->id,
        'citizen_identifier' => '87654321',
        'case_subtype_id' => $caseSubtype->id,
        'status' => 'open',
    ]);
});

it('allows operator to add a new manual call record from history page', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $role = Role::firstOrCreate(
        ['name' => 'operator', 'guard_name' => 'web'],
        ['code' => 'OP', 'hierarchy_level' => 1],
    );

    $user->assignRole($role);
    Permission::firstOrCreate(['name' => 'call_records.update', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'call_records.create', 'guard_name' => 'web']);
    $user->givePermissionTo('call_records.update');
    $user->givePermissionTo('call_records.create');

    $channel = Channel::create([
        'id' => Str::ulid(),
        'name' => 'Salud',
        'description' => 'Servicios de Salud',
        'is_active' => true,
    ]);

    $queue = CallQueue::create([
        'name' => 'Servicios de Salud',
        'description' => 'Cola de servicios médicos',
        'is_active' => true,
        'channel_id' => $channel->id,
    ]);

    $subtype = CaseSubtype::create([
        'code' => 'MANUAL_CONSULTA',
        'queue_id' => $queue->id,
        'name' => 'Consulta manual',
    ]);

    Livewire::actingAs($user)
        ->test(CreateCallRecord::class)
        ->set('form.phone_number', '6612-9999')
        ->set('form.citizen_identifier', '12345678')
        ->set('form.queue_id', $queue->id)
        ->set('form.case_subtype_id', $subtype->id)
        ->set('form.description', 'Registro manual de historial telefónico.')
        ->set('form.status', 'open')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('call_records', [
        'phone_number' => '6612-9999',
        'citizen_identifier' => '12345678',
        'case_subtype_id' => $subtype->id,
        'status' => 'open',
        'employee_id' => $employee->id,
    ]);
});
