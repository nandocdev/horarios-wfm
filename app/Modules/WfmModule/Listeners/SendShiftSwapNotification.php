<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Listeners;

use App\Modules\WfmModule\Notifications\ShiftSwapApprovedNotification;
use App\Modules\WfmModule\Notifications\SwapRequestNotification;
use App\Modules\WfmModule\Notifications\SwapStatusChangedNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Events\ShiftSwapAccepted;
use App\Shared\Events\ShiftSwapApproved;
use App\Shared\Events\ShiftSwapCancelled;
use App\Shared\Events\ShiftSwapRejected;
use App\Shared\Events\ShiftSwapRejectedByPeer;
use App\Shared\Events\ShiftSwapRequested;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendShiftSwapNotification implements ShouldQueue
{
    public function handleShiftSwapRequested(ShiftSwapRequested $event): void
    {
        $swap = $event->shiftSwap;

        $dateRange = $swap->start_date->format('d/m/Y');
        if ($swap->end_date && $swap->end_date->gt($swap->start_date)) {
            $dateRange .= ' al '.$swap->end_date->format('d/m/Y');
        }

        $dto = new NotificationDTO(
            title: 'Nueva Solicitud de Intercambio',
            message: "{$swap->requester->first_name} {$swap->requester->last_name} ha solicitado intercambiar un turno contigo para el periodo {$dateRange}.",
            actionUrl: route('schedules.swap-history'),
            icon: 'arrows-right-left',
            level: 'info',
        );

        if ($swap->recipient?->user) {
            $swap->recipient->user->notify(new SwapRequestNotification($swap));
        }

        if ($swap->recipient?->team?->supervisor?->user) {
            $supervisorDto = new NotificationDTO(
                title: 'Solicitud de Intercambio Pendiente',
                message: "{$swap->recipient->first_name} {$swap->recipient->last_name} tiene una solicitud de intercambio pendiente de {$swap->requester->first_name} {$swap->requester->last_name} para el periodo {$dateRange}.",
                actionUrl: route('schedules.swap-history'),
                icon: 'arrows-right-left',
                level: 'info',
            );
            $swap->recipient->team->supervisor->user->notify(new SwapRequestNotification($swap));
        }
    }

    public function handleShiftSwapApproved(ShiftSwapApproved $event): void
    {
        $swap = $event->shiftSwap;

        $dateRange = $swap->start_date->format('d/m/Y');
        if ($swap->end_date && $swap->end_date->gt($swap->start_date)) {
            $dateRange .= ' al '.$swap->end_date->format('d/m/Y');
        }

        $dto = new NotificationDTO(
            title: 'Cambio de Turno Aprobado',
            message: "El intercambio de turno para el periodo {$dateRange} ha sido aprobado y aplicado.",
            actionUrl: route('schedules.my-schedule'),
            icon: 'check-circle',
            level: 'success',
        );

        if ($swap->requester?->user) {
            $swap->requester->user->notify(new ShiftSwapApprovedNotification($dto));
        }

        if ($swap->recipient?->user) {
            $swap->recipient->user->notify(new ShiftSwapApprovedNotification($dto));
        }
    }

    public function handleShiftSwapRejected(ShiftSwapRejected $event): void
    {
        $swap = $event->shiftSwap;

        $dateRange = $swap->start_date->format('d/m/Y');
        if ($swap->end_date && $swap->end_date->gt($swap->start_date)) {
            $dateRange .= ' al '.$swap->end_date->format('d/m/Y');
        }

        $dto = new NotificationDTO(
            title: 'Cambio de Turno Rechazado',
            message: "El intercambio de turno para el periodo {$dateRange} ha sido rechazado. Motivo: {$event->reason}",
            actionUrl: route('schedules.swap-history'),
            icon: 'x-circle',
            level: 'danger',
        );

        if ($swap->requester?->user) {
            $swap->requester->user->notify(new SwapStatusChangedNotification($dto));
        }

        if ($swap->recipient?->user) {
            $swap->recipient->user->notify(new SwapStatusChangedNotification($dto));
        }
    }

    public function handleShiftSwapCancelled(ShiftSwapCancelled $event): void
    {
        $swap = $event->shiftSwap;

        $dateRange = $swap->start_date->format('d/m/Y');
        if ($swap->end_date && $swap->end_date->gt($swap->start_date)) {
            $dateRange .= ' al '.$swap->end_date->format('d/m/Y');
        }

        $dto = new NotificationDTO(
            title: 'Solicitud de Intercambio Cancelada',
            message: "El solicitante ha cancelado la solicitud de intercambio para el periodo {$dateRange}.",
            actionUrl: route('schedules.swap-history'),
            icon: 'x-circle',
            level: 'warning',
        );

        if ($swap->recipient?->user) {
            $swap->recipient->user->notify(new SwapStatusChangedNotification($dto));
        }
    }

    public function handleShiftSwapAccepted(ShiftSwapAccepted $event): void
    {
        $swap = $event->shiftSwap;

        $dateRange = $swap->start_date->format('d/m/Y');
        if ($swap->end_date && $swap->end_date->gt($swap->start_date)) {
            $dateRange .= ' al '.$swap->end_date->format('d/m/Y');
        }

        $notifyDto = new NotificationDTO(
            title: 'Intercambio Aceptado — Pendiente de Aprobación',
            message: "Tu solicitud de intercambio para el periodo {$dateRange} ha sido aceptada. Queda pendiente de aprobación por WFM.",
            actionUrl: route('schedules.swap-history'),
            icon: 'check-circle',
            level: 'success',
        );

        if ($swap->requester?->user) {
            $swap->requester->user->notify(new SwapStatusChangedNotification($notifyDto));
        }

        $coordinatorDto = new NotificationDTO(
            title: 'Intercambio de Turno Aceptado — Pendiente de Aprobación',
            message: "{$swap->requester->first_name} {$swap->requester->last_name} y {$swap->recipient->first_name} {$swap->recipient->last_name} han acordado un intercambio para el periodo {$dateRange}. Requiere aprobación de WFM.",
            actionUrl: route('schedules.wfm-approvals'),
            icon: 'arrows-right-left',
            level: 'info',
        );

        if ($swap->requester?->manager?->user) {
            $swap->requester->manager->user->notify(new SwapStatusChangedNotification($coordinatorDto));
        }

        if ($swap->recipient?->manager?->user) {
            $swap->recipient->manager->user->notify(new SwapStatusChangedNotification($coordinatorDto));
        }
    }

    public function handleShiftSwapRejectedByPeer(ShiftSwapRejectedByPeer $event): void
    {
        $swap = $event->shiftSwap;

        $dateRange = $swap->start_date->format('d/m/Y');
        if ($swap->end_date && $swap->end_date->gt($swap->start_date)) {
            $dateRange .= ' al '.$swap->end_date->format('d/m/Y');
        }

        $dto = new NotificationDTO(
            title: 'Intercambio Rechazado',
            message: "Tu solicitud de intercambio para el periodo {$dateRange} ha sido rechazada por el destinatario.",
            actionUrl: route('schedules.swap-history'),
            icon: 'x-circle',
            level: 'danger',
        );

        if ($swap->requester?->user) {
            $swap->requester->user->notify(new SwapStatusChangedNotification($dto));
        }
    }
}
