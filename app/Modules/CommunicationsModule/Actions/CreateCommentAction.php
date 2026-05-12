<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\CommentDTO;
use App\Modules\CommunicationsModule\Events\CommentCreated;
use App\Modules\CommunicationsModule\Models\Comment;
use App\Modules\CommunicationsModule\Models\News;
use Illuminate\Support\Facades\DB;

/**
 * Crea un nuevo comentario en una noticia.
 */
class CreateCommentAction
{
    /**
     * Ejecuta la creación del comentario.
     *
     * @param  CommentDTO  $dto  Datos validados del comentario
     * @param  News  $news  La noticia donde se crea el comentario
     * @param  int  $userId  ID del usuario que crea el comentario
     * @return Comment El comentario creado
     */
    public function execute(CommentDTO $dto, News $news, int $userId): Comment
    {
        return DB::transaction(function () use ($dto, $news, $userId) {
            $comment = Comment::create([
                'news_id' => $news->id,
                'user_id' => $userId,
                'content' => $dto->content,
                'parent_id' => $dto->parentId,
                'is_active' => true,
            ]);

            event(new CommentCreated($comment));

            return $comment;
        });
    }
}
