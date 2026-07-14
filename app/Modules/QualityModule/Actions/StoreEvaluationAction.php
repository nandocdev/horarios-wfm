<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\DTOs\CreateEvaluationDTO;
use App\Modules\QualityModule\Events\EvaluationCreated;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\EvaluationRedFlag;
use App\Modules\QualityModule\Models\EvaluationScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StoreEvaluationAction
{
    public function execute(CreateEvaluationDTO $dto): Evaluation
    {
        return DB::transaction(function () use ($dto) {
            $score = collect($dto->scores)->sum('puntaje');

            $evaluation = Evaluation::create([
                'queue_id' => $dto->queue_id,
                'employee_id' => $dto->employee_id,
                'evaluator_id' => $dto->evaluator_id,
                'clip_id' => $dto->clip_id,
                'dtcall' => $dto->dtcall,
                'tmcall' => $dto->tmcall,
                'dteval' => $dto->dteval,
                'tmeval' => $dto->tmeval,
                'score' => $score,
                'callobs' => $dto->callobs,
                'has_redflag' => ! empty($dto->red_flags),
                'status' => 'activa',
            ]);

            if (! empty($dto->scores)) {
                $scoresData = array_map(
                    fn (array $s) => [
                        'id' => (string) Str::ulid(),
                        'evaluation_id' => $evaluation->id,
                        'criteria_version_id' => $s['criteria_version_id'],
                        'puntaje_obtenido' => $s['puntaje'],
                    ],
                    $dto->scores
                );

                EvaluationScore::insert($scoresData);
            }

            if (! empty($dto->red_flags)) {
                $redFlagsData = array_map(
                    fn (array $rf) => [
                        'id' => (string) Str::ulid(),
                        'evaluation_id' => $evaluation->id,
                        'red_flag_criteria_id' => $rf['red_flag_criteria_id'],
                    ],
                    $dto->red_flags
                );

                EvaluationRedFlag::insert($redFlagsData);
            }

            EvaluationCreated::dispatch($evaluation);

            return $evaluation;
        });
    }
}
