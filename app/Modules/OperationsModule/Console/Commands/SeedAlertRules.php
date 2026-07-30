<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Console\Commands;

use App\Modules\OperationsModule\Alerts\Models\AlertRule;
use Illuminate\Console\Command;

final class SeedAlertRules extends Command
{
    protected $signature = 'alerts:seed-rules';

    protected $description = 'Crea o actualiza las reglas de alerta predeterminadas';

    public function handle(): int
    {
        $this->info('Sembrando reglas de alerta...');

        $defaults = [
            [
                'event_type' => 'adherence.alert',
                'label' => 'Alerta de Adherencia',
                'description' => 'Agente fuera de adherencia por más de 5 minutos.',
                'threshold_seconds' => 300,
                'escalation_minutes' => [5, 15, 30],
                'escalation_roles' => ['supervisor', 'coordinator', 'chief'],
                'cooldown_minutes' => 15,
            ],
            [
                'event_type' => 'agent.no_login',
                'label' => 'Agente sin Login',
                'description' => 'Agente no ha iniciado sesión a la hora programada.',
                'threshold_seconds' => 300,
                'escalation_minutes' => [10, 20, 30],
                'escalation_roles' => ['supervisor', 'coordinator', 'chief'],
                'cooldown_minutes' => 30,
            ],
            [
                'event_type' => 'agent.break_exceeded',
                'label' => 'Descanso Excedido',
                'description' => 'Agente ha excedido el tiempo de descanso.',
                'threshold_seconds' => 900,
                'escalation_minutes' => [5, 15],
                'escalation_roles' => ['supervisor', 'coordinator'],
                'cooldown_minutes' => 10,
            ],
            [
                'event_type' => 'agent.lunch_exceeded',
                'label' => 'Almuerzo Excedido',
                'description' => 'Agente ha excedido el tiempo de almuerzo.',
                'threshold_seconds' => 3600,
                'escalation_minutes' => [10, 20],
                'escalation_roles' => ['supervisor', 'coordinator'],
                'cooldown_minutes' => 15,
            ],
            [
                'event_type' => 'agent.unexpected_logout',
                'label' => 'Logout Inesperado',
                'description' => 'Agente se desconectó durante su turno sin permiso.',
                'threshold_seconds' => 60,
                'escalation_minutes' => [5, 15, 30],
                'escalation_roles' => ['supervisor', 'coordinator', 'chief'],
                'cooldown_minutes' => 10,
            ],
            [
                'event_type' => 'agent.upcoming_shift_reminder',
                'label' => 'Recordatorio de Turno',
                'description' => 'Recordatorio al agente sobre inicio próximo de turno.',
                'threshold_seconds' => 1800,
                'cooldown_minutes' => 1440,
            ],
        ];

        $count = 0;

        foreach ($defaults as $data) {
            AlertRule::updateOrCreate(
                ['event_type' => $data['event_type']],
                $data,
            );
            $count++;
        }

        $this->info("{$count} reglas de alerta creadas/actualizadas.");

        return self::SUCCESS;
    }
}
