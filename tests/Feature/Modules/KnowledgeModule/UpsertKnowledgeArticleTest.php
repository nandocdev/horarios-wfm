<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\KnowledgeModule;

use App\Modules\CoreModule\Models\User;
use App\Modules\KnowledgeModule\Livewire\UpsertKnowledgeArticle;
use App\Modules\KnowledgeModule\Models\ArticleVersion;
use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use App\Modules\KnowledgeModule\Models\KnowledgeCategory;
use App\Modules\KnowledgeModule\Models\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['knowledge.viewAny', 'knowledge.manage'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->supervisor = User::factory()->create();
    $this->supervisor->givePermissionTo('knowledge.manage');

    $this->category = KnowledgeCategory::create(['name' => 'Procedimientos']);
    $this->queue = Queue::create([
        'name' => 'Cancelación Policlínicas',
        'priority' => 10,
        'is_active' => true,
    ]);
});

test('supervisor can create an article with queues and tags', function () {
    $this->actingAs($this->supervisor);

    Livewire::test(UpsertKnowledgeArticle::class)
        ->set('form.title', 'Cómo cancelar una cita')
        ->set('form.summary', 'Resumen')
        ->set('form.content', '<p>Paso 1</p>')
        ->set('form.category_id', $this->category->id)
        ->set('form.status', 'published')
        ->set('form.queues', [$this->queue->id])
        ->set('form.tagsString', 'citas, laboratorio')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('knowledge_articles', [
        'title' => 'Cómo cancelar una cita',
        'status' => 'published',
        'version' => 1,
        'created_by' => $this->supervisor->id,
    ]);

    $article = KnowledgeArticle::where('title', 'Cómo cancelar una cita')->first();
    expect($article->queues->pluck('id')->toArray())->toContain($this->queue->id);
    expect($article->tags->pluck('name')->toArray())->toBe(['citas', 'laboratorio']);
    expect(ArticleVersion::where('article_id', $article->id)->count())->toBe(1);
});

test('malicious scripts are stripped from content on create', function () {
    $this->actingAs($this->supervisor);

    Livewire::test(UpsertKnowledgeArticle::class)
        ->set('form.title', 'Artículo seguro')
        ->set('form.content', '<p>Texto</p><script>alert(1)</script><img src="x" onerror="alert(2)">')
        ->set('form.queues', [$this->queue->id])
        ->call('save')
        ->assertHasNoErrors();

    $article = KnowledgeArticle::where('title', 'Artículo seguro')->first();

    expect($article->content)->not->toContain('<script');
    expect($article->content)->not->toContain('onerror');
    expect($article->content)->toContain('<p>Texto</p>');
});

test('editing content increments version and keeps the slug immutable', function () {
    $this->actingAs($this->supervisor);

    $article = KnowledgeArticle::create([
        'title' => 'Título original',
        'slug' => 'titulo-original-abc123',
        'content' => 'Contenido v1',
        'status' => 'published',
        'published_at' => now(),
        'created_by' => $this->supervisor->id,
    ]);
    $article->queues()->sync([$this->queue->id]);

    ArticleVersion::create([
        'article_id' => $article->id,
        'version' => 1,
        'content' => 'Contenido v1',
        'created_by' => $this->supervisor->id,
        'created_at' => now(),
    ]);

    Livewire::test(UpsertKnowledgeArticle::class, ['id' => $article->id])
        ->set('form.title', 'Nuevo título')
        ->set('form.content', 'Contenido v2')
        ->set('form.queues', [$this->queue->id])
        ->call('save')
        ->assertHasNoErrors();

    $article->refresh();

    expect($article->version)->toBe(2);
    expect($article->slug)->toBe('titulo-original-abc123');
    expect($article->content)->toBe('Contenido v2');
    expect(ArticleVersion::where('article_id', $article->id)->count())->toBe(2);
});

test('operator without manage permission cannot access the create page', function () {
    $operator = User::factory()->create();
    $operator->givePermissionTo('knowledge.viewAny');
    $this->actingAs($operator);

    $this->get(route('knowledge.create'))->assertForbidden();
});
