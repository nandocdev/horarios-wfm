<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Console\Commands;

use App\Modules\OperationsModule\Alerts\Evaluators\AdherenceEvaluator;
use App\Modules\OperationsModule\Alerts\Evaluators\BreakExceededEvaluator;
use App\Modules\OperationsModule\Alerts\Evaluators\LunchExceededEvaluator;
use App\Modules\OperationsModule\Alerts\Evaluators\NoLoginEvaluator;
use App\Modules\OperationsModule\Alerts\Evaluators\UnexpectedLogoutEvaluator;
use App\Modules\OperationsModule\Alerts\Evaluators\UpcomingShiftReminderEvaluator;
use App\Modules\OperationsModule\Alerts\Models\AlertRule;
use Illuminate\Console\Command;

final class EvaluateAlertsCommand extends Command
{
    protected $signature = 'alerts:evaluate
        {--evaluator= : Evaluador especifico (adherence, no_login, break, lunch, logout, reminder)}';

    protected $description = 'Evalua todas las reglas de alerta activas';

    private array $evaluators = [
        AdherenceEvaluator::class,
        NoLoginEvaluator::class,
        BreakExceededEvaluator::class,
        LunchExceededEvaluator::class,
        UnexpectedLogoutEvaluator::class,
        UpcomingShiftReminderEvaluator::class,
    ];

    public function handle(): int
    {
        $filter = $this->option('evaluator');
        $evaluatorInstances = [];

        foreach ($this->evaluators as $evaluatorClass) {
            $instance = app($evaluatorClass);
            $eventType = $instance->eventType();

            if ($filter && ! str_contains($eventType, $filter)) {
                continue;
            }

            $evaluatorInstances[$eventType] = $instance;
        }

        $rules = AlertRule::where('is_enabled', true)
            ->whereIn('event_type', array_keys($evaluatorInstances))
            ->get()
            ->keyBy('event_type');

        foreach ($evaluatorInstances as $eventType => $evaluator) {
            $rule = $rules->get($eventType);

            if (! $rule) {
                continue;
            }

            $this->info("Evaluando: {$rule->label} ({$eventType})");

            try {
                $evaluator->evaluate($rule);
            } catch (\Throwable $e) {
                $this->error("Error evaluando {$eventType}: {$e->getMessage()}");
                logger()->error('[Alerts] Error en evaluador', [
                    'event_type' => $eventType,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Evaluacion de alertas completada.');

        return self::SUCCESS;
    }
}
