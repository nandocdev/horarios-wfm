<?php

declare(strict_types=1);

use App\Modules\DocumentationModule\Livewire\Admin\ManageArticles;
use App\Modules\DocumentationModule\Livewire\Public\ArticleDetail;
use App\Modules\DocumentationModule\Livewire\Public\ArticleIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Rutas públicas de documentación
    Route::get('/docs', ArticleIndex::class)->name('documentation.index');
    Route::get('/docs/{slug}', ArticleDetail::class)->name('documentation.show');

    // Rutas de administración
    Route::middleware(['can:articles.manage'])->prefix('admin/documentation')->group(function () {
        Route::get('/articles', ManageArticles::class)->name('documentation.admin.articles');
    });
});
