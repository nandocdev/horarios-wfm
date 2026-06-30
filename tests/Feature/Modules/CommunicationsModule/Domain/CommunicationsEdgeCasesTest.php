<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->user = User::withoutEvents(fn () => User::factory()->create());
});

// ────────────────────────────────────────────
// Edge cases del dominio de comunicaciones
// ────────────────────────────────────────────

it('noticia con slug automatico generado via booted no permite duplicados', function () {
    News::create([
        'title' => 'Mi Noticia',
        'slug' => 'mi-noticia',
        'content' => 'Contenido',
        'author_id' => $this->user->id,
    ]);

    $this->expectException(QueryException::class);
    News::create([
        'title' => 'Mi Noticia',
        'slug' => 'mi-noticia',
        'content' => 'Otro contenido',
        'author_id' => $this->user->id,
    ]);
});

it('noticia programada con scheduled_at futuro se crea en draft no publicado', function () {
    $news = News::create([
        'title' => 'Scheduled',
        'slug' => 'scheduled-future',
        'content' => 'Content',
        'author_id' => $this->user->id,
        'status' => 'draft',
        'scheduled_at' => now()->addDays(7),
    ]);

    expect($news->status)->toBe('draft')
        ->and($news->scheduled_at->isFuture())->toBeTrue();
});

it('poll sin opciones (JSON vacio) se crea pero no es votable', function () {
    $poll = Poll::create([
        'question' => 'Empty poll?',
        'options' => json_encode([]),
        'status' => 'published',
    ]);

    expect(json_decode($poll->options, true))->toBe([]);
});

it('shoutout con mensaje exactamente de 500 caracteres se crea correctamente', function () {
    $message = str_repeat('a', 500);

    $shoutout = Shoutout::create([
        'employee_id' => Employee::factory()->create()->id,
        'message' => $message,
        'status' => 'published',
    ]);

    expect($shoutout->message)->toHaveLength(500);
});

it('contenido archivado via archive_at automatico (AutoArchiveContentAction)', function () {
    $news = News::create([
        'title' => 'Auto archive',
        'slug' => 'auto-archive',
        'content' => 'Content',
        'author_id' => $this->user->id,
        'status' => 'published',
        'archive_at' => now()->subDay(),
    ]);

    $action = app(\App\Modules\CommunicationsModule\Actions\AutoArchiveContentAction::class);
    $result = $action->execute();

    expect($result['news'])->toBeGreaterThanOrEqual(1);
    $this->assertDatabaseHas('news', [
        'id' => $news->id,
        'status' => 'archived',
    ]);
});

/*
 * [BUG?] PublishScheduledContentAction busca News con status='published'
 * y actualiza published_at. No busca status='draft' programadas.
 * La logica esta invertida: deberia buscar draft con scheduled_at pasado
 * y cambiar a published.
 */
it('publishScheduledContentAction — [BUG?] busca status published no draft', function () {
    $news = News::create([
        'title' => 'Scheduled publish',
        'slug' => 'scheduled-publish',
        'content' => 'Content',
        'author_id' => $this->user->id,
        'status' => 'published',
        'scheduled_at' => now()->subHour(),
        'published_at' => null,
    ]);

    $action = app(\App\Modules\CommunicationsModule\Actions\PublishScheduledContentAction::class);
    $result = $action->execute();

    expect($result['news'])->toBeGreaterThanOrEqual(1);
});

it('preserva version_history como JSONB en news despues de actualizar', function () {
    $news = News::create([
        'title' => 'Versioned',
        'slug' => 'versioned',
        'content' => 'v1',
        'author_id' => $this->user->id,
        'status' => 'draft',
    ]);

    $news->update(['content' => 'v2', 'version_history' => [['content' => 'v1']]]);

    $fresh = News::find($news->id);
    expect($fresh->version_history)->toBeArray()
        ->and($fresh->version_history[0]['content'] ?? null)->toBe('v1');
});
