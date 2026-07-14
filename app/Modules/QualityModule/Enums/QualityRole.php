<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Enums;

enum QualityRole: string
{
    case Evaluator = 'quality-evaluator';
    case Coordinator = 'quality-coordinator';
    case Supervisor = 'quality-supervisor';
    case Admin = 'quality-admin';

    public function label(): string
    {
        return match ($this) {
            self::Evaluator => 'Evaluador',
            self::Coordinator => 'Coordinador de Calidad',
            self::Supervisor => 'Supervisor de Calidad',
            self::Admin => 'Administrador de Calidad',
        };
    }

    /**
     * @return string[]
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Evaluator => [
                'quality.evaluations.view',
                'quality.evaluations.create',
            ],
            self::Supervisor => [
                'quality.evaluations.view',
                'quality.evaluations.create',
                'quality.feedback.create',
            ],
            self::Coordinator => [
                'quality.evaluations.view',
                'quality.evaluations.create',
                'quality.evaluations.delete',
                'quality.feedback.create',
                'quality.calibrations.create',
            ],
            self::Admin => [
                'quality.evaluations.view',
                'quality.evaluations.create',
                'quality.evaluations.delete',
                'quality.feedback.create',
                'quality.calibrations.create',
                'quality.criteria.view',
                'quality.criteria.create',
                'quality.criteria.update',
                'quality.queues.manage',
                'quality.dashboard.view',
            ],
        };
    }
}
