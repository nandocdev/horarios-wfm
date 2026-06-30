<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Events\CommentCreated;
use App\Modules\CommunicationsModule\Events\MentionCreated;
use App\Modules\CommunicationsModule\Events\ReactionAdded;
use App\Modules\CommunicationsModule\Events\ReactionRemoved;
use App\Modules\CommunicationsModule\Models\Comment;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;

beforeEach(function () {
    $this->user = User::withoutEvents(fn () => User::factory()->create());
});

// ────────────────────────────────────────────
// Eventos de dominio internos
// ────────────────────────────────────────────

it('CommentCreated se dispara exactamente una vez al crear comentario via CreateCommentAction', function () {
    Event::fake();

    $news = News::create([
        'title' => 'Event test',
        'slug' => 'event-test',
        'content' => 'Content',
        'author_id' => $this->user->id,
        'status' => 'published',
    ]);

    $action = app(\App\Modules\CommunicationsModule\Actions\CreateCommentAction::class);
    $action->execute(
        new \App\Modules\CommunicationsModule\DTOs\CommentDTO('Comment', null),
        $news,
        $this->user->id,
    );

    Event::assertDispatched(CommentCreated::class, 1);
});

/*
 * [BUG?] ToggleReactionAction no se puede testear via DTO porque ReactionDTO
 * requiere ReactionType enum inexistente. Tests de eventos de reaccion
 * se omiten hasta que se cree el enum.
 */

/*
 * [BUG?] Mismo problema que en Actions: ProcessMentionsAction busca por username
 * y User no tiene esa columna. Test omitido hasta que se resuelva.
 */

// ────────────────────────────────────────────
// Observers — invalidacion de cache
// ────────────────────────────────────────────

it('NewsObserver invalida cache al crear noticia', function () {
    Cache::put('news_list', ['existing'], 60);

    News::create([
        'title' => 'Cache test',
        'slug' => 'cache-test',
        'content' => 'Content',
        'author_id' => $this->user->id,
    ]);

    expect(Cache::has('news_list'))->toBeFalse();
});

it('CommentObserver invalida cache de comentarios al crear via Eloquent', function () {
    $news = News::create([
        'title' => 'Comment cache',
        'slug' => 'comment-cache',
        'content' => 'Content',
        'author_id' => $this->user->id,
    ]);
    Cache::put('news_comments:'.$news->id, ['cached'], 60);

    // Usar Eloquent para que el observer se dispare
    Comment::create([
        'news_id' => $news->id,
        'user_id' => $this->user->id,
        'content' => 'Flush cache',
        'is_active' => true,
    ]);

    expect(Cache::has('news_comments:'.$news->id))->toBeFalse();
});

// ────────────────────────────────────────────
// Eventos cross-module (Shared)
// ────────────────────────────────────────────

it('WeeklySchedulePublished tiene listener registrado en el provider', function () {
    $listeners = Event::getListeners(\App\Shared\Events\WeeklySchedulePublished::class);

    // Event::getListeners devuelve array de callables — verificamos que exista al menos uno
    expect($listeners)->not->toBeEmpty();
});
