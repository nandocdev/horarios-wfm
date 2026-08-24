<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Listeners;

use App\Modules\WfmModule\Notifications\ShiftSwapApprovedNotification;
use App\Modules\WfmModule\Notifications\SwapRequestNotification;
use App\Modules\WfmModule\Notifications\SwapStatusChangedNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Enums\NotificationType;
use App\Shared\Events\ShiftSwapAccepted;
use App\Shared\Events\ShiftSwapApproved;
use App\Shared\Events\ShiftSwapCancelled;
use App\Shared\Events\ShiftSwapRejected;
use App\Shared\Events\ShiftSwapRejectedByPeer;
use App\Shared\Events\ShiftSwapRequested;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendShiftSwapNotification implements ShouldQueue
{
    private function periodLabel($swap): string
    {
        $label = $swap->start_date->format('d/m/Y');
        if ($swap->end_date && $swap->end_date->gt($swap->start_date)) {
            $label .= ' al '.$swap->end_date->format('d/m/Y');
        }

        return $label;
    }

    private function requesterName($swap): string
    {
        return $swap->requester?->name ?? 'Usuario';
    }

    private function recipientName($swap): string
    {
        return $swap->recipient?->name ?? 'Usuario';
    }

    public function handleShiftSwapRequested(ShiftSwapRequested $event): void
    {
        $swap = $event->shiftSwap;
        $period = $this->periodLabel($swap);

        $dto = new NotificationDTO(
            title: 'Nueva solicitud de intercambio',
            message: "{$this->requesterName($swap)} desea intercambiar un turno contigo.",
            summary: "{$this->requesterName($swap)} ha solicitado intercambiar un turno contigo para el periodo {$period}.",
            actionUrl: route('schedules.swap-history'),
            icon: 'arrows-right-left',
            level: 'info',
            notificationType: NotificationType::ShiftSwapRequested->value,
            facts: [
                ['label' => 'Periodo', 'value' => $period],
                ['label' => 'Solicitante', 'value' => $this->requesterName($swap)],
                ['label' => 'Estado', 'value' => 'Pendiente de tu respuesta'],
            ],
            recommendation: 'Acepta o rechaza la solicitud desde tu historial de intercambios.',
            resourceType: 'shift_swap',
            resourceId: (string) $swap->id,
        );

        if ($swap->recipient) {
            $swap->recipient->notify(new SwapRequestNotification($dto));
        }

        if ($supervisor = $swap->recipient?->employee?->team?->supervisor) {
            $supervisorDto = new NotificationDTO(
                title: 'Solicitud de intercambio pendiente',
                message: "{$this->recipientName($swap)} tiene una solicitud pendiente.",
                summary: "{$this->recipientName($swap)} tiene una solicitud de intercambio pendiente de {$this->requesterName($swap)} para el periodo {$period}.",
                actionUrl: route('schedules.swap-history'),
                icon: 'arrows-right-left',
                level: 'info',
                notificationType: NotificationType::ShiftSwapRequested->value,
                facts: [
                    ['label' => 'Periodo', 'value' => $period],
                    ['label' => 'Solicitante', 'value' => $this->requesterName($swap)],
                    ['label' => 'Destinatario', 'value' => $this->recipientName($swap)],
                    ['label' => 'Estado', 'value' => 'Pendiente de aprobación'],
                ],
                resourceType: 'shift_swap',
                resourceId: (string) $swap->id,
            );
            $supervisor->notify(new SwapRequestNotification($supervisorDto));
        }
    }

    public function handleShiftSwapApproved(ShiftSwapApproved $event): void
    {
        $swap = $event->shiftSwap;
        $period = $this->periodLabel($swap);

        $dto = new NotificationDTO(
            title: 'Intercambio aprobado',
            message: "El intercambio para el periodo {$period} fue aprobado y aplicado.",
            summary: 'El intercambio fue aprobado y aplicado correctamente.',
            actionUrl: route('schedules.my-schedule'),
            icon: 'check-circle',
            level: 'success',
            notificationType: NotificationType::ShiftSwapApproved->value,
            facts: [
                ['label' => 'Periodo', 'value' => $period],
                ['label' => 'Solicitante', 'value' => $this->requesterName($swap)],
                ['label' => 'Destinatario', 'value' => $this->recipientName($swap)],
                ['label' => 'Estado', 'value' => 'Completado'],
            ],
            recommendation: 'No se requiere ninguna acción adicional.',
            resourceType: 'shift_swap',
            resourceId: (string) $swap->id,
        );

        if ($swap->requester) {
            $swap->requester->notify(new ShiftSwapApprovedNotification($dto));
        }

        if ($swap->recipient) {
            $swap->recipient->notify(new ShiftSwapApprovedNotification($dto));
        }
    }

    public function handleShiftSwapRejected(ShiftSwapRejected $event): void
    {
        $swap = $event->shiftSwap;
        $period = $this->periodLabel($swap);

        $dto = new NotificationDTO(
            title: 'Intercambio rechazado',
            message: "El intercambio para el periodo {$period} no fue aprobado.",
            summary: 'La solicitud no fue aprobada.',
            actionUrl: route('schedules.swap-history'),
            icon: 'x-circle',
            level: 'danger',
            notificationType: NotificationType::ShiftSwapRejected->value,
            facts: [
                ['label' => 'Periodo', 'value' => $period],
                ['label' => 'Motivo', 'value' => $event->reason],
                ['label' => 'Estado', 'value' => 'Rechazado'],
            ],
            recommendation: 'Puedes crear una nueva solicitud para otra fecha.',
            resourceType: 'shift_swap',
            resourceId: (string) $swap->id,
        );

        if ($swap->requester) {
            $swap->requester->notify(new SwapStatusChangedNotification($dto));
        }

        if ($swap->recipient) {
            $swap->recipient->notify(new SwapStatusChangedNotification($dto));
        }
    }

    public function handleShiftSwapCancelled(ShiftSwapCancelled $event): void
    {
        $swap = $event->shiftSwap;
        $period = $this->periodLabel($swap);

        $dto = new NotificationDTO(
            title: 'Solicitud de intercambio cancelada',
            message: "La solicitud para el periodo {$period} fue cancelada.",
            summary: 'El solicitante ha cancelado la solicitud de intercambio.',
            actionUrl: route('schedules.swap-history'),
            icon: 'x-circle',
            level: 'warning',
            notificationType: NotificationType::ShiftSwapCancelled->value,
            facts: [
                ['label' => 'Periodo', 'value' => $period],
                ['label' => 'Estado', 'value' => 'Cancelado'],
            ],
            resourceType: 'shift_swap',
            resourceId: (string) $swap->id,
        );

        if ($swap->recipient) {
            $swap->recipient->notify(new SwapStatusChangedNotification($dto));
        }
    }

    public function handleShiftSwapAccepted(ShiftSwapAccepted $event): void
    {
        $swap = $event->shiftSwap;
        $period = $this->periodLabel($swap);

        $notifyDto = new NotificationDTO(
            title: 'Intercambio aceptado',
            message: "Tu solicitud para el periodo {$period} fue aceptada.",
            summary: 'Tu solicitud de intercambio ha sido aceptada. Queda pendiente de aprobación por WFM.',
            actionUrl: route('schedules.swap-history'),
            icon: 'check-circle',
            level: 'success',
            notificationType: NotificationType::ShiftSwapAccepted->value,
            facts: [
                ['label' => 'Periodo', 'value' => $period],
                ['label' => 'Solicitante', 'value' => $this->requesterName($swap)],
                ['label' => 'Destinatario', 'value' => $this->recipientName($swap)],
                ['label' => 'Estado', 'value' => 'Aceptado — Pendiente de aprobación WFM'],
            ],
            recommendation: 'Espera la aprobación del equipo WFM.',
            resourceType: 'shift_swap',
            resourceId: (string) $swap->id,
        );

        if ($swap->requester) {
            $swap->requester->notify(new SwapStatusChangedNotification($notifyDto));
        }

        $coordinatorDto = new NotificationDTO(
            title: 'Intercambio aceptado — pendiente de aprobación',
            message: "{$this->requesterName($swap)} y {$this->recipientName($swap)} acordaron un intercambio.",
            summary: "{$this->requesterName($swap)} y {$this->recipientName($swap)} han acordado un intercambio para el periodo {$period}. Requiere aprobación de WFM.",
            actionUrl: route('schedules.wfm-approvals'),
            icon: 'arrows-right-left',
            level: 'info',
            notificationType: NotificationType::ShiftSwapAccepted->value,
            facts: [
                ['label' => 'Periodo', 'value' => $period],
                ['label' => 'Solicitante', 'value' => $this->requesterName($swap)],
                ['label' => 'Destinatario', 'value' => $this->recipientName($swap)],
                ['label' => 'Estado', 'value' => 'Pendiente de aprobación WFM'],
            ],
            recommendation: 'Revisa y aprueba o rechaza la solicitud.',
            resourceType: 'shift_swap',
            resourceId: (string) $swap->id,
        );

        if ($requesterManager = $swap->requester?->employee?->manager?->user) {
            $requesterManager->notify(new SwapStatusChangedNotification($coordinatorDto));
        }

        if ($recipientManager = $swap->recipient?->employee?->manager?->user) {
            $recipientManager->notify(new SwapStatusChangedNotification($coordinatorDto));
        }
    }

    public function handleShiftSwapRejectedByPeer(ShiftSwapRejectedByPeer $event): void
    {
        $swap = $event->shiftSwap;
        $period = $this->periodLabel($swap);

        $dto = new NotificationDTO(
            title: 'Intercambio rechazado',
            message: "Tu solicitud para el periodo {$period} fue rechazada por el destinatario.",
            summary: 'Tu solicitud de intercambio ha sido rechazada por el destinatario.',
            actionUrl: route('schedules.swap-history'),
            icon: 'x-circle',
            level: 'danger',
            notificationType: NotificationType::ShiftSwapRejected->value,
            facts: [
                ['label' => 'Periodo', 'value' => $period],
                ['label' => 'Destinatario', 'value' => $this->recipientName($swap)],
                ['label' => 'Estado', 'value' => 'Rechazado'],
            ],
            recommendation: 'Puedes crear una nueva solicitud para otra fecha.',
            resourceType: 'shift_swap',
            resourceId: (string) $swap->id,
        );

        if ($swap->requester) {
            $swap->requester->notify(new SwapStatusChangedNotification($dto));
        }
    }
}
