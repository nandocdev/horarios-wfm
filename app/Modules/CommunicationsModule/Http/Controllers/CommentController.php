<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CommunicationsModule\Actions\CreateCommentAction;
use App\Modules\CommunicationsModule\DTOs\CommentDTO;
use App\Modules\CommunicationsModule\Http\Requests\StoreCommentRequest;
use App\Modules\CommunicationsModule\Models\News;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    /**
     * Almacena un nuevo comentario.
     */
    public function store(
        StoreCommentRequest $request,
        News $news,
        CreateCommentAction $action,
    ): RedirectResponse {
        $dto = CommentDTO::fromArray($request->validated());
        $comment = $action->execute($dto, $news, $request->user()->id);

        return redirect()
            ->back()
            ->with('success', 'Comentario agregado correctamente.');
    }
}
