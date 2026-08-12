<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Listeners;

use App\Modules\ConnectModule\Notifications\SyncFailedNotification;
use App\Modules\CoreModule\Models\User;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Enums\NotificationType;
use App\Shared\Events\SyncFailed;
use Illuminate\Support\Facades\Log;

class SendSyncFailedNotification
{
    public function handle(SyncFailed $event): void
    {
        $dto = new NotificationDTO(
            title: 'Fallo de Sincronización',
            message: "La sincronización ha fallado: {$event->source}",
            summary: $event->message,
            actionUrl: '#',
            icon: 'exclamation-circle',
            level: 'critical',
            notificationType: NotificationType::SyncFailed->value,
            facts: [
                ['label' => 'Origen', 'value' => $event->source],
                ['label' => 'Mensaje', 'value' => $event->message],
                ['label' => 'Fallos Consecutivos', 'value' => (string) $event->consecutiveFailures],
            ],
            recommendation: 'Revisar los logs del servidor para mayor detalle.',
            resourceType: 'system',
            resourceId: 'sync',
        );

        /** @var User|null $user */
        $user = User::where('email', 'ferncastillo@css.gob.pa')->first();

        if ($user) {
            $user->notify(new SyncFailedNotification($dto));

            Log::info('[Connect] Notificación de fallo de sincronización enviada a ferncastillo.', [
                'source' => $event->source,
            ]);
        } else {
            Log::warning('[Connect] Usuario ferncastillo no encontrado para enviar SyncFailedNotification.');
        }
    }
}
