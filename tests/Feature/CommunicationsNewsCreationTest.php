<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Actions\CreateNewsAction;
use App\Modules\CommunicationsModule\DTOs\NewsDTO;
use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Tag;
use App\Modules\CoreModule\Models\User;

it('creates news and syncs categories and tags as draft', function () {
    $author = User::factory()->create();

    $categoryA = Category::query()->create([
        'name' => 'Operaciones',
        'slug' => 'operaciones-'.uniqid(),
        'is_active' => true,
    ]);

    $categoryB = Category::query()->create([
        'name' => 'Tecnología',
        'slug' => 'tecnologia-'.uniqid(),
        'is_active' => true,
    ]);

    $tagA = Tag::query()->create([
        'name' => 'Urgente',
        'slug' => 'urgente-'.uniqid(),
        'is_active' => true,
    ]);

    $tagB = Tag::query()->create([
        'name' => 'WFM',
        'slug' => 'wfm-'.uniqid(),
        'is_active' => true,
    ]);

    $dto = new NewsDTO(
        title: 'Nueva noticia operativa',
        slug: 'nueva-noticia-operativa-'.uniqid(),
        excerpt: 'Resumen breve',
        content: 'Contenido completo de la noticia',
        published_at: now()->addHour()->toDateTimeString(),
        scheduled_at: null,
        archive_at: null,
        categoryIds: [$categoryA->id, $categoryB->id],
        tagIds: [$tagA->id, $tagB->id],
        workflowAction: 'save_draft',
        is_active: true,
        author_id: $author->id,
    );

    /** @var CreateNewsAction $action */
    $action = app(CreateNewsAction::class);
    $news = $action->execute($dto);

    expect($news->status)->toBe('draft')
        ->and($news->categories()->pluck('categories.id')->all())
        ->toMatchArray([$categoryA->id, $categoryB->id])
        ->and($news->tags()->pluck('tags.id')->all())
        ->toMatchArray([$tagA->id, $tagB->id]);
});

it('creates news in pending review when requested', function () {
    $author = User::factory()->create();

    $dto = new NewsDTO(
        title: 'Noticia para revisión',
        slug: 'noticia-para-revision-'.uniqid(),
        excerpt: null,
        content: 'Contenido sujeto a revisión',
        published_at: now()->addHour()->toDateTimeString(),
        scheduled_at: null,
        archive_at: null,
        categoryIds: [],
        tagIds: [],
        workflowAction: 'submit_review',
        is_active: true,
        author_id: $author->id,
    );

    /** @var CreateNewsAction $action */
    $action = app(CreateNewsAction::class);
    $news = $action->execute($dto);

    expect($news->status)->toBe('pending_review')
        ->and(News::query()->whereKey($news->id)->exists())->toBeTrue();
});
