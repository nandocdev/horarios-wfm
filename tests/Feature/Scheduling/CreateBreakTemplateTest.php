<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use App\Modules\SchedulingModule\Actions\CreateBreakTemplateAction;
use App\Modules\SchedulingModule\DTOs\CreateBreakTemplateDTO;
use App\Modules\SchedulingModule\Livewire\CreateBreakTemplate;
use App\Modules\SchedulingModule\Models\BreakTemplate;
use App\Modules\SchedulingModule\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('creates break template through action and prevents duplicates', function () {
    $schedule = Schedule::create([
        'name' => 'Turno Mañana',
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'lunch_minutes' => 45,
        'break_minutes' => 15,
        'total_minutes' => 540,
        'is_active' => true,
    ]);

    $dto = new CreateBreakTemplateDTO(
        schedule_id: $schedule->id,
        name: 'Descanso café',
        start_time: '10:30',
        duration_minutes: 15,
    );

    $action = app(CreateBreakTemplateAction::class);
    $template = $action->execute($dto);

    expect($template)->toBeInstanceOf(BreakTemplate::class);
    expect($template->name)->toBe('Descanso café');

    expect(DB::table('break_templates')->where('schedule_id', $schedule->id)->count())->toBe(1);

    $this->expectException(InvalidArgumentException::class);
    $action->execute($dto);
});

it('allows wfm role creating break template via livewire', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(
        ['name' => 'wfm', 'guard_name' => 'web'],
        ['code' => 'WFM', 'hierarchy_level' => 5]
    );
    $user->assignRole($role);
    Permission::firstOrCreate(['name' => 'break_templates.create', 'guard_name' => 'web']);
    $user->givePermissionTo('break_templates.create');

    $schedule = Schedule::create([
        'name' => 'Turno Tarde',
        'start_time' => '15:00:00',
        'end_time' => '23:00:00',
        'lunch_minutes' => 30,
        'break_minutes' => 15,
        'total_minutes' => 500,
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(CreateBreakTemplate::class)
        ->set('form.schedule_id', $schedule->id)
        ->set('form.name', 'Descanso intermedio')
        ->set('form.start_time', '18:00')
        ->set('form.duration_minutes', 20)
        ->call('save');

    $this->assertDatabaseHas('break_templates', [
        'schedule_id' => $schedule->id,
        'name' => 'Descanso intermedio',
    ]);
});
