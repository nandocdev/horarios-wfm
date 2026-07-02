<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Http\Controllers;

use App\Src\Platform\Application\DTOs\ReactionDTO;
use App\Src\Platform\Application\Handlers\ToggleReactionHandler;
use App\Src\Platform\Presentation\Policies\ReactionPolicy;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class ReactionController extends Controller
{
    public function store(Request $request, Shoutout $shoutout, ToggleReactionHandler $handler): JsonResponse
    {
        $this->authorize('create', ReactionPolicy::class);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:like,love,celebrate,support,insightful'],
        ]);

        $dto = ReactionDTO::fromArray($validated);
        $reaction = $handler->execute($dto, $shoutout->id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => $reaction ? 'Reacción agregada.' : 'Reacción removida.',
            'data' => [
                'reaction_added' => $reaction !== null,
                'reaction_type' => $reaction?->type?->value,
                'reaction_count' => $shoutout->activeReactions()->where('type', $dto->type->value)->count(),
            ],
        ]);
    }
}
