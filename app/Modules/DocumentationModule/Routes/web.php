<?php

declare(strict_types=1);

use App\Modules\DocumentationModule\Livewire\Admin\ManageWikiArticles;
use App\Modules\DocumentationModule\Livewire\Public\WikiArticleDetail;
use App\Modules\DocumentationModule\Livewire\Public\WikiArticleIndex;
use Illuminate\Support\Facades\Route;

// Rutas públicas de documentación (Acceso para todos los autenticados)
Route::get('/docs', WikiArticleIndex::class)->name('documentation.index');
Route::get('/docs/{slug}', WikiArticleDetail::class)->name('documentation.show');

// Rutas de administración
Route::middleware(['can:articles.manage'])->prefix('admin/documentation')->group(function () {
    Route::get('/articles', ManageWikiArticles::class)->name('documentation.admin.articles');
});
