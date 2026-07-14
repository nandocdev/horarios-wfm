<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\DTOs\CreateFeedbackDTO;
use App\Modules\QualityModule\Events\FeedbackAdded;
use App\Modules\QualityModule\Models\Feedback;

final class StoreFeedbackAction
{
    public function execute(CreateFeedbackDTO $dto): Feedback
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($dto) {
            $evaluation = \App\Modules\QualityModule\Models\Evaluation::findOrFail($dto->evaluation_id);
            
            $feedback = Feedback::create([
                'evaluation_id' => $dto->evaluation_id,
                'obsfeed' => $dto->obsfeed,
                'created_by' => $dto->created_by,
            ]);
            
            $evaluation->update([
                'status' => \App\Modules\QualityModule\Enums\EvaluationStatus::ConFeedback->value,
            ]);
    
            FeedbackAdded::dispatch($feedback);
    
            return $feedback;
        });
    }
}
