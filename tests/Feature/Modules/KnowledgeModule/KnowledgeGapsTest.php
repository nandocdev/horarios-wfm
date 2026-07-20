<?php

declare(strict_types=1);

use App\Modules\KnowledgeModule\Policies\ArticleVersionPolicy;
use App\Modules\KnowledgeModule\Policies\KnowledgeCategoryPolicy;

test('KnowledgeCategoryPolicy existe', function () {
    expect(class_exists(KnowledgeCategoryPolicy::class))->toBeTrue();
});

test('ArticleVersionPolicy existe', function () {
    expect(class_exists(ArticleVersionPolicy::class))->toBeTrue();
});

test('KnowledgeCategoryPolicy registrada en ModuleServiceProvider', function () {
    $contents = file_get_contents(app_path('Modules/KnowledgeModule/Providers/ModuleServiceProvider.php'));
    expect($contents)->toContain('KnowledgeCategoryPolicy');
    expect($contents)->toContain('ArticleVersionPolicy');
});

test('UpsertKnowledgeArticle usa KnowledgeArticleForm', function () {
    $contents = file_get_contents(app_path('Modules/KnowledgeModule/Livewire/UpsertKnowledgeArticle.php'));
    expect($contents)->toContain('KnowledgeArticleForm');
    expect($contents)->toContain('$this->form->validate()');
});
