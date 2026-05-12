<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CommunicationsModule\Actions\ToggleReactionAction;
use App\Modules\CommunicationsModule\DTOs\ReactionDTO;
use App\Modules\CommunicationsModule\Http\Requests\StoreReactionRequest;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Http\JsonResponse;

class ReactionController extends Controller
{
    /**
     * Almacena o remueve una reacción.
     */
    public function store(
        StoreReactionRequest $request,
        Shoutout $shoutout,
        ToggleReactionAction $action,
    ): JsonResponse {
        $dto = ReactionDTO::fromArray($request->validated());
        $reaction = $action->execute($dto, $shoutout, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => $reaction ? 'Reacción agregada.' : 'Reacción removida.',
            'data' => [
                'reaction_added' => $reaction !== null,
                'reaction_type' => $reaction?->type->value,
                'reaction_count' => $shoutout->activeReactions()->where('type', $dto->type)->count(),
            ],
        ]);
    }
}
