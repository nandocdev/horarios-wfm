<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\DTOs\CreateFeedbackDTO;
use App\Modules\QualityModule\Enums\EvaluationStatus;
use App\Modules\QualityModule\Events\FeedbackAdded;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\Feedback;
use Illuminate\Support\Facades\DB;

final class StoreFeedbackAction
{
    public function execute(CreateFeedbackDTO $dto): Feedback
    {
        return DB::transaction(function () use ($dto) {
            $evaluation = Evaluation::findOrFail($dto->evaluation_id);

            $feedback = Feedback::create([
                'evaluation_id' => $dto->evaluation_id,
                'obsfeed' => $dto->obsfeed,
                'created_by' => $dto->created_by,
            ]);

            $evaluation->update([
                'status' => EvaluationStatus::ConFeedback->value,
            ]);

            FeedbackAdded::dispatch($feedback);

            return $feedback;
        });
    }
}
