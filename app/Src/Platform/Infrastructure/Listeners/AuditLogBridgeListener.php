<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Src\Platform\Infrastructure\Persistence\AuditLogBridge;
use Illuminate\Contracts\Queue\ShouldQueue;

final class AudittLogBridgeListener implements ShouldQueue {
    public function handle(object $event): void {
        $entity = match (true) {
            property_exists($event, 'record') => $event->record,
            property_exists($event, 'callRecord') => $event->callRecord,
            default => $event,
        };

        $action = match (true) {
            $event instanceof \App\Src\Connect\Domain\Events\CallRecordStarted => 'call_record.started',
            $event instanceof \App\Src\Connect\Domain\Events\CallRecordCompleted => 'call_record.completed',
            $event instanceof \App\Src\Connect\Domain\Events\CallRecordClosed => 'call_record.closed',
            default => class_basename($event),
        };

        AuditLogBridge::logCustom(
            entityType: get_class($entity),
            entityId: $entity instanceof \Illuminate\Database\Eloquent\Model ? $entity->getKey() : $entity->id ?? null,
            action: $action,
            before: null,
            after: method_exists($entity, 'toArray') ? $entity->toArray() : (array) $entity,
            ipAddress: null,
            userId: auth()->id(),
        );
    }
}
