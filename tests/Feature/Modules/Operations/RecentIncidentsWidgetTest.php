<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Operations;

use App\Modules\OperationsModule\Actions\JustifyAttendanceIncidentAction;
use App\Modules\OperationsModule\Livewire\Widgets\RecentIncidentsWidget;
use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\OperationsModule\Models\IncidentType;
use App\Modules\PersonnelModule\Models\Employee;
use Database\Seeders\IncidentTypeSeeder;
use Livewire\Livewire;
use Tests\TestCase;

class RecentIncidentsWidgetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IncidentTypeSeeder::class);
    }

    public function test_it_renders_recent_incidents_widget_correctly(): void
    {
        $employee = Employee::factory()->create();
        $incidentType = IncidentType::first();

        $incident = AttendanceIncident::create([
            'employee_id' => $employee->id,
            'incident_type_id' => $incidentType->id,
            'incident_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        Livewire::withoutLazyLoading()
            ->test(RecentIncidentsWidget::class)
            ->assertStatus(200)
            ->assertSee($employee->first_name)
            ->assertSee($incidentType->name)
            ->assertSee('Pendiente');
    }

    public function test_it_justifies_an_incident_via_widget_and_action(): void
    {
        $employee = Employee::factory()->create();
        $incidentType = IncidentType::first();

        $incident = AttendanceIncident::create([
            'employee_id' => $employee->id,
            'incident_type_id' => $incidentType->id,
            'incident_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $this->assertNull($incident->admin_comment);

        // Ejecutar justificación desde el widget Livewire
        Livewire::withoutLazyLoading()
            ->test(RecentIncidentsWidget::class)
            ->call('justify', $incident->id)
            ->assertStatus(200);

        // Refrescar y validar base de datos
        $incident->refresh();
        $this->assertEquals('Justificado desde Dashboard', $incident->admin_comment);

        // El widget ahora debe mostrarlo como "Justificada"
        Livewire::withoutLazyLoading()
            ->test(RecentIncidentsWidget::class)
            ->assertSee('Justificada')
            ->assertDontSeeHtml('Justificar');
    }

    public function test_action_justifies_incident_correctly(): void
    {
        $employee = Employee::factory()->create();
        $incidentType = IncidentType::first();

        $incident = AttendanceIncident::create([
            'employee_id' => $employee->id,
            'incident_type_id' => $incidentType->id,
            'incident_date' => now()->toDateString(),
        ]);

        $action = resolve(JustifyAttendanceIncidentAction::class);
        $result = $action->execute($incident->id, 'Comentario Test');

        $this->assertEquals('Comentario Test', $result->admin_comment);
        $this->assertEquals('Comentario Test', $incident->refresh()->admin_comment);
    }
}
