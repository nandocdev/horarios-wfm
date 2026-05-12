<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Notification;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\PollResponse;
use App\Modules\CoreModule\Models\User;

it('auto archives communications content by archive_at date', function () {
    $author = User::factory()->create();

    $news = News::query()->create([
        'title' => 'Noticia vencida',
        'slug' => 'noticia-vencida-'.uniqid(),
        'excerpt' => 'Resumen',
        'content' => 'Contenido',
        'author_id' => $author->id,
        'is_active' => true,
        'published_at' => now()->subDay(),
        'status' => 'published',
        'archive_at' => now()->subMinute(),
    ]);

    $poll = Poll::query()->create([
        'question' => '¿Encuesta expirada?',
        'options' => [
            ['label' => 'Sí', 'value' => 'yes'],
            ['label' => 'No', 'value' => 'no'],
        ],
        'is_active' => true,
        'status' => 'published',
        'archive_at' => now()->subMinute(),
    ]);

    $this->artisan('communications:auto-archive')
        ->assertExitCode(0);

    expect($news->fresh()->status)->toBe('archived')
        ->and($news->fresh()->is_active)->toBeFalse()
        ->and($poll->fresh()->status)->toBe('archived')
        ->and($poll->fresh()->is_active)->toBeFalse();
});

it('sends reminders for expired polls only to users who did not vote', function () {
    $userVoted = User::factory()->create();
    $userNotVoted = User::factory()->create();

    $poll = Poll::query()->create([
        'question' => 'Encuesta para recordatorio',
        'options' => [
            ['label' => 'A', 'value' => 'a'],
            ['label' => 'B', 'value' => 'b'],
        ],
        'is_active' => true,
        'status' => 'published',
        'expires_at' => now()->subHour(),
        'reminder_sent_at' => null,
    ]);

    PollResponse::query()->create([
        'poll_id' => $poll->id,
        'user_id' => $userVoted->id,
        'answer' => 'a',
    ]);

    $this->artisan('communications:send-expired-poll-reminders')
        ->assertExitCode(0);

    expect(Notification::query()
        ->where('type', 'poll_expired')
        ->where('user_id', $userNotVoted->id)
        ->where('notifiable_type', Poll::class)
        ->where('notifiable_id', $poll->id)
        ->exists())->toBeTrue();

    expect(Notification::query()
        ->where('type', 'poll_expired')
        ->where('user_id', $userVoted->id)
        ->where('notifiable_id', $poll->id)
        ->exists())->toBeFalse();

    expect($poll->fresh()->reminder_sent_at)->not->toBeNull();
});
