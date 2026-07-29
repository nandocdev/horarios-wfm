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
        $definitions = [
            ['CSQ_FARMACIA', 'Gestión de disponibilidad, estatus de recetas y dispensación.', true, 125, 'Inbound'],
            ['CSQ_CANCELACION_CITAS', 'Liberación de cupos médicos y gestión de inasistencias.', true, 135, 'Inbound'],
            ['CSQ_CONFIRMACION_CITAS', 'Verificación de requisitos, horario y sede de citas programadas.', true, 165, 'Inbound'],
            ['CSQ_CENTRO_CONTACTO', 'Agendamiento de medicina general, especialidades y laboratorios.', true, 180, 'Inbound'],
            ['CSQ_INFORMACION_GENERAL', 'Orientación sobre trámites, requisitos de afiliación y ubicación de unidades.', true, 110, 'Inbound'],
            ['CSQ_SIPE', 'Soporte técnico y administrativo del Sistema de Ingresos y Prestaciones.', true, 210, 'Inbound'],
            ['CSQ_QUEJAS', 'Registro y seguimiento de insatisfacciones y procesos de auditoría social.', true, 140, 'Inbound'],
            ['CSQ_Outbound_Cobros', 'Gestión de cobros y comunicaciones salientes.', false, null, 'Outbound'],
        ];

        foreach ($definitions as [$name, $description, $isActive, $ahtGoal, $channelName]) {
            $channel = Channel::where('name', $channelName)->first();

            CallQueue::updateOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'is_active' => $isActive,
                    'aht_goal' => $ahtGoal,
                    'channel_id' => $channel?->id,
                ],
            );
        }

        $inboundId = Channel::where('name', 'Inbound')->value('id');
        if ($inboundId) {
            CallQueue::whereNull('channel_id')->update(['channel_id' => $inboundId]);
        }
    }
}
