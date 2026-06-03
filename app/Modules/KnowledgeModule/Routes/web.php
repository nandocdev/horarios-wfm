<?php

declare(strict_types=1);

use App\Modules\KnowledgeModule\Livewire\ArticleDetail;
use App\Modules\KnowledgeModule\Livewire\ManageArticles;
use App\Modules\KnowledgeModule\Livewire\OperatorView;
use Illuminate\Support\Facades\Route;

// Rutas accesibles para personal operativo autenticado
Route::middleware(['auth'])->group(function () {
    Route::get('/knowledge', OperatorView::class)->name('knowledge.index');
    Route::get('/knowledge/{slug}', ArticleDetail::class)->name('knowledge.show');
});

// Rutas de gestión administrativa
Route::middleware(['auth', 'can:knowledge.manage'])->group(function () {
    Route::get('/admin/knowledge', ManageArticles::class)->name('knowledge.admin');
    Route::get('/admin/knowledge/create', \App\Modules\KnowledgeModule\Livewire\UpsertArticle::class)->name('knowledge.create');
    Route::get('/admin/knowledge/{id}/edit', \App\Modules\KnowledgeModule\Livewire\UpsertArticle::class)->name('knowledge.edit');
});
