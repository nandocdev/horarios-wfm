<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Listeners;

use App\Modules\QualityModule\Events\EvaluationCreated;
use App\Modules\QualityModule\Notifications\EvaluationNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Enums\NotificationType;
use Illuminate\Support\Facades\Log;

class SendEvaluationNotification
{
    public function handle(EvaluationCreated $event): void
    {
        $evaluation = $event->evaluation;

        $evaluation->loadMissing('employee.user');

        if (! $evaluation->employee?->user) {
            return;
        }

        $dto = new NotificationDTO(
            title: 'Nueva evaluación de calidad',
            message: "Tu llamada del {$evaluation->dtcall->format('d/m/Y')} ha sido evaluada.",
            summary: "Tu gestión ha sido evaluada con un puntaje de {$evaluation->score}%.",
            actionUrl: route('quality.evaluations.show', $evaluation->id),
            icon: 'clipboard-document-check',
            level: 'info',
            notificationType: NotificationType::EvaluationCreated->value,
            facts: [
                ['label' => 'Fecha de llamada', 'value' => $evaluation->dtcall->format('d/m/Y')],
                ['label' => 'Puntaje', 'value' => $evaluation->score.'%'],
            ],
            recommendation: 'Revisa tu evaluación y el feedback asociado en tu portal de calidad.',
            resourceType: 'evaluation',
            resourceId: (string) $evaluation->id,
        );

        $evaluation->employee->user->notify(new EvaluationNotification($dto));

        Log::info('[Quality] Evaluacion notificada al empleado', [
            'evaluation_id' => $evaluation->id,
            'employee_id' => $evaluation->employee_id,
            'score' => $evaluation->score,
        ]);
    }
}
