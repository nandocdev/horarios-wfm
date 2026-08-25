<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Operations;

use App\Modules\CoreModule\Models\User;
use App\Modules\OperationsModule\Livewire\AgentPerformanceDashboard;
use App\Modules\OperationsModule\Services\AgentPerformanceService;
use App\Modules\PersonnelModule\Models\Employee;
use Database\Seeders\IncidentTypeSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AgentPerformanceDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IncidentTypeSeeder::class);
    }

    public function test_agent_can_view_own_dashboard(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(AgentPerformanceDashboard::class)
            ->assertStatus(200)
            ->assertSee('John Doe')
            ->assertDontSeeHtml('Seleccionar Agente'); // No tiene permisos de supervisor
    }

    public function test_supervisor_can_view_and_switch_agents_via_selector(): void
    {
        $supervisorUser = User::factory()->create();
        Employee::factory()->create(['first_name' => 'Boss', 'user_id' => $supervisorUser->id]);

        // Asignar permiso de visualización general
        $permission = Permission::findOrCreate('agent.performance.view', 'web');
        $supervisorUser->givePermissionTo($permission);

        $agent = Employee::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $this->actingAs($supervisorUser);

        Livewire::test(AgentPerformanceDashboard::class, ['employee' => $agent->id])
            ->assertStatus(200)
            ->assertSee('John Doe')
            ->assertSee('Seleccionar Agente') // Sí ve el selector
            ->set('employeeId', $supervisorUser->employee->id)
            ->assertSee('Boss');
    }

    public function test_service_returns_correct_empty_dto_structure(): void
    {
        $employee = Employee::factory()->create();
        $service = resolve(AgentPerformanceService::class);

        $result = $service->getPerformance($employee, 5);

        $this->assertIsArray($result->summary);
        $this->assertIsArray($result->days);
        $this->assertIsArray($result->stateDistribution);
        $this->assertIsArray($result->queueDetail);
        $this->assertIsArray($result->deviations);

        $this->assertEquals(0, $result->summary['avg_adherence']);
        $this->assertEquals(0, $result->summary['total_calls']);
    }
}
