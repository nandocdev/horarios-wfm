<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Models\Category;
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
// Unique constraints
// ────────────────────────────────────────────

it('slug de news es unique', function () {
    News::create([
        'title' => 'First',
        'slug' => 'same-slug',
        'content' => 'Content',
        'author_id' => $this->user->id,
    ]);

    $this->expectException(QueryException::class);
    News::create([
        'title' => 'Second',
        'slug' => 'same-slug',
        'content' => 'Other content',
        'author_id' => $this->user->id,
    ]);
});

it('slug de category es unique', function () {
    Category::create(['name' => 'A', 'slug' => 'dup']);
    $this->expectException(QueryException::class);
    Category::create(['name' => 'B', 'slug' => 'dup']);
});

it('poll_responses tiene unique(poll_id, user_id) — un voto por usuario', function () {
    $poll = Poll::create([
        'question' => 'Test?',
        'options' => json_encode([['label' => 'Si', 'value' => 'si']]),
        'status' => 'published',
    ]);

    DB::table('poll_responses')->insert([
        'poll_id' => $poll->id,
        'user_id' => $this->user->id,
        'answer' => 'si',
    ]);

    $this->expectException(QueryException::class);
    DB::table('poll_responses')->insert([
        'poll_id' => $poll->id,
        'user_id' => $this->user->id,
        'answer' => 'no',
    ]);
});

it('categorizables tiene unique(category_id, categorizable_type, categorizable_id)', function () {
    $news = News::create([
        'title' => 'Cat test',
        'slug' => 'cat-test',
        'content' => 'Content',
        'author_id' => $this->user->id,
    ]);
    $cat = Category::create(['name' => 'Test', 'slug' => 'test-cat']);

    $news->categories()->attach($cat->id);

    $this->expectException(QueryException::class);
    $news->categories()->attach($cat->id);
});

// ────────────────────────────────────────────
// CHECK constraints / Enums
// ────────────────────────────────────────────

it('status en news solo acepta draft|pending_review|published|archived', function () {
    $this->expectException(QueryException::class);
    DB::table('news')->insert([
        'title' => 'Bad status',
        'slug' => 'bad-status',
        'content' => 'Content',
        'author_id' => $this->user->id,
        'status' => 'invalid_status',
    ]);
});

it('status en polls solo acepta draft|pending_review|published|archived', function () {
    $this->expectException(QueryException::class);
    DB::table('polls')->insert([
        'question' => 'Test?',
        'options' => '[]',
        'status' => 'deleted',
    ]);
});

// ────────────────────────────────────────────
// ON DELETE CASCADE
// ────────────────────────────────────────────

it('eliminar news cascada a comments', function () {
    $news = News::create([
        'title' => 'Cascade',
        'slug' => 'cascade',
        'content' => 'Content',
        'author_id' => $this->user->id,
    ]);
    DB::table('comments')->insert([
        'news_id' => $news->id,
        'user_id' => $this->user->id,
        'content' => 'Comment',
        'is_active' => true,
    ]);

    $news->delete();

    $this->assertDatabaseMissing('news', ['id' => $news->id]);
    $this->assertDatabaseMissing('comments', ['news_id' => $news->id]);
});

it('eliminar poll cascada a poll_responses', function () {
    $poll = Poll::create([
        'question' => 'Cascade?',
        'options' => json_encode([['label' => 'Si', 'value' => 'si']]),
        'status' => 'published',
    ]);
    DB::table('poll_responses')->insert([
        'poll_id' => $poll->id,
        'user_id' => $this->user->id,
        'answer' => 'si',
    ]);

    $poll_id = $poll->id;
    $poll->delete();

    $this->assertDatabaseMissing('polls', ['id' => $poll_id]);
    $this->assertDatabaseMissing('poll_responses', ['poll_id' => $poll_id]);
});

it('eliminar categoria cascada a categorizables', function () {
    $news = News::create([
        'title' => 'Cat cascade',
        'slug' => 'cat-cascade',
        'content' => 'Content',
        'author_id' => $this->user->id,
    ]);
    $cat = Category::create(['name' => 'CascadeCat', 'slug' => 'cascade-cat']);
    $news->categories()->attach($cat->id);

    $cat_id = $cat->id;
    $cat->delete();

    $this->assertDatabaseMissing('categorizables', ['category_id' => $cat_id]);
});

it('eliminar shoutout cascada a reactions', function () {
    $shoutout = Shoutout::create([
        'employee_id' => Employee::factory()->create()->id,
        'message' => 'Reaction test',
        'status' => 'published',
    ]);
    DB::table('reactions')->insert([
        'shoutout_id' => $shoutout->id,
        'user_id' => $this->user->id,
        'type' => 'like',
        'is_active' => true,
    ]);

    $shoutout_id = $shoutout->id;
    $shoutout->delete();

    $this->assertDatabaseMissing('shoutouts', ['id' => $shoutout_id]);
    $this->assertDatabaseMissing('reactions', ['shoutout_id' => $shoutout_id]);
});
