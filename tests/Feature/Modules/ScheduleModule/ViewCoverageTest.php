<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Livewire\ViewCoverage;
use App\Modules\WfmModule\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_range_calculation_with_schedules()
    {
        // Crear algunos schedules con diferentes horas
        Schedule::create([
            'name' => 'Turno Mañana',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'total_minutes' => 480,
            'is_overnight' => false,
            'is_active' => true,
        ]);

        Schedule::create([
            'name' => 'Turno Temprano',
            'start_time' => '06:00:00',
            'end_time' => '14:00:00',
            'total_minutes' => 480,
            'is_overnight' => false,
            'is_active' => true,
        ]);

        Schedule::create([
            'name' => 'Turno Tarde',
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'total_minutes' => 480,
            'is_overnight' => false,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ViewCoverage::class)
            ->assertSet('useAutoRange', true)
            ->assertSet('operatingStartTime', '06:00')
            ->assertSet('operatingEndTime', '18:00');
    }

    public function test_manual_range_toggle()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ViewCoverage::class)
            ->set('useAutoRange', false)
            ->assertSet('operatingStartTime', '06:00')
            ->assertSet('operatingEndTime', '17:00');
    }

    public function test_auto_range_toggle_updates_times()
    {
        // Crear schedule temprano
        Schedule::create([
            'name' => 'Turno Especial',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
            'total_minutes' => 480,
            'is_overnight' => false,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ViewCoverage::class)
            ->set('useAutoRange', false)
            ->set('operatingStartTime', '09:00')
            ->set('operatingEndTime', '17:00')
            ->set('useAutoRange', true)
            ->assertSet('operatingStartTime', '07:00')
            ->assertSet('operatingEndTime', '15:00');
    }
}
