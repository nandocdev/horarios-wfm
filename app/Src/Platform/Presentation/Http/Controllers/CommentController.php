<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Http\Controllers;

use App\Src\Platform\Application\DTOs\CommentDTO;
use App\Src\Platform\Application\Handlers\CreateCommentHandler;
use App\Src\Platform\Presentation\Policies\CommentPolicy;
use App\Modules\CommunicationsModule\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CommentController extends Controller
{
    public function store(Request $request, News $news, CreateCommentHandler $handler): RedirectResponse
    {
        $this->authorize('create', CommentPolicy::class);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:communications_comments,id'],
        ]);

        $dto = CommentDTO::fromArray($validated);
        $handler->execute($dto, $news->id, $request->user()->id);

        return redirect()->back()->with('success', 'Comentario agregado correctamente.');
    }
}
