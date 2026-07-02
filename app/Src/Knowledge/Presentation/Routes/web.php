<?php

declare(strict_types=1);

use App\Src\Knowledge\Presentation\Livewire\ArticleSearch;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('knowledge')->group(function () {
    Route::get('/', ArticleSearch::class)->name('knowledge.search');
});
