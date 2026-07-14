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
        $feedback = Feedback::create([
            'evaluation_id' => $dto->evaluation_id,
            'obsfeed' => $dto->obsfeed,
            'created_by' => $dto->created_by,
        ]);

        FeedbackAdded::dispatch($feedback);

        return $feedback;
    }
}
