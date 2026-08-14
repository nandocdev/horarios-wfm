<?php

declare(strict_types=1);

use App\Modules\KnowledgeModule\Livewire\KnowledgeArticleDetail;
use App\Modules\KnowledgeModule\Livewire\ManageKnowledgeArticles;
use App\Modules\KnowledgeModule\Livewire\OperatorView;
use App\Modules\KnowledgeModule\Livewire\UpsertKnowledgeArticle;
use Illuminate\Support\Facades\Route;

// Rutas accesibles para personal operativo autenticado
Route::middleware(['auth'])->group(function () {
    Route::get('/knowledge', OperatorView::class)->name('knowledge.index');
    Route::get('/knowledge/{slug}', KnowledgeArticleDetail::class)->name('knowledge.show');
});

// Rutas de gestión administrativa
Route::middleware(['auth', 'permission:knowledge.manage'])->group(function () {
    Route::get('/admin/knowledge', ManageKnowledgeArticles::class)->name('knowledge.admin');
    Route::get('/admin/knowledge/create', UpsertKnowledgeArticle::class)->name('knowledge.create');
    Route::get('/admin/knowledge/{id}/edit', UpsertKnowledgeArticle::class)->name('knowledge.edit');
});
