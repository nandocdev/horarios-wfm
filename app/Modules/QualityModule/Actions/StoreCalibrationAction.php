<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\DTOs\CreateCalibrationDTO;
use App\Modules\QualityModule\Events\CalibrationCreated;
use App\Modules\QualityModule\Models\CalibrationLog;
use App\Modules\QualityModule\Models\Evaluation;
use Illuminate\Support\Facades\DB;

final class StoreCalibrationAction
{
    public function execute(CreateCalibrationDTO $dto): CalibrationLog
    {
        return DB::transaction(function () use ($dto) {
            $evaluation = Evaluation::findOrFail($dto->evaluation_id);

            $scoreAnterior = $evaluation->score;

            $calibration = CalibrationLog::create([
                'evaluation_id' => $dto->evaluation_id,
                'score_anterior' => $scoreAnterior,
                'score_nuevo' => $dto->score_nuevo,
                'obs' => $dto->obs,
                'created_by' => $dto->created_by,
            ]);

            $evaluation->update([
                'score' => $dto->score_nuevo,
                'status' => \App\Modules\QualityModule\Enums\EvaluationStatus::Activa->value,
            ]);

            CalibrationCreated::dispatch($calibration);

            return $calibration;
        });
    }
}
