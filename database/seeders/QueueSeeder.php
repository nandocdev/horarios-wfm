<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\Channel;
use Illuminate\Database\Seeder;

class QueueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // $queues = [name, description, is_active, aht_goal_in_seconds]
        $queues = [
            ['CSQ_FARMACIA', 'Gestión de disponibilidad, estatus de recetas y dispensación.', true, 125], // 00:02:05
            ['CSQ_CANCELACION_CITAS', 'Liberación de cupos médicos y gestión de inasistencias.', true, 135], // 00:02:15
            ['CSQ_CONFIRMACION_CITAS', 'Verificación de requisitos, horario y sede de citas programadas.', true, 165], // 00:02:45
            ['CSQ_CENTRO_CONTACTO', 'Agendamiento de medicina general, especialidades y laboratorios.', true, 180], // 00:03:00 (CITAS MÉDICAS)
            ['CSQ_INFORMACION_GENERAL', 'Orientación sobre trámites, requisitos de afiliación y ubicación de unidades.', true, 110], // 00:01:50
            ['CSQ_SIPE', 'Soporte técnico y administrativo del Sistema de Ingresos y Prestaciones.', true, 210], // 00:03:30
            ['CSQ_QUEJAS', 'Registro y seguimiento de insatisfacciones y procesos de auditoría social.', true, 140], // 00:02:20
            ['CSQ_Outbound_Cobros', 'Gestión de cobros y comunicaciones salientes.', false, null],
        ];

        foreach ($queues as $queue) {
            // Inferir canal por convención en el nombre de la cola
            $queueName = $queue[0];
            // Use strpos for broader PHP compatibility
            $channelName = strpos(strtolower($queueName), 'outbound') !== false ? 'Outbound' : 'Inbound';
            $channel = Channel::where('name', $channelName)->first();

            CallQueue::updateOrCreate([
                'name' => $queueName,
            ], [
                'description' => $queue[1],
                'is_active' => $queue[2],
                'aht_goal' => $queue[3],
                'channel_id' => $channel?->id,
            ]);
        }

        // Asegurar que las colas existentes sin canal asignado queden en 'Inbound' por defecto
        $inboundId = Channel::where('name', 'Inbound')->value('id');
        if ($inboundId) {
            CallQueue::where(function ($q) {
                $q->whereNull('channel_id')->orWhere('channel_id', '');
            })->update(['channel_id' => $inboundId]);
        }
    }
}
