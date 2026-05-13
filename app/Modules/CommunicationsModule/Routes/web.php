<?php

declare(strict_types=1);

use App\Modules\CommunicationsModule\Http\Controllers\CategoryController;
use App\Modules\CommunicationsModule\Http\Controllers\CommentController;
use App\Modules\CommunicationsModule\Http\Controllers\ContentModerationController;
use App\Modules\CommunicationsModule\Http\Controllers\ReactionController;
use App\Modules\CommunicationsModule\Http\Controllers\TagController;
use App\Modules\CommunicationsModule\Livewire\CreateNews;
use App\Modules\CommunicationsModule\Livewire\EditNews;
use App\Modules\CommunicationsModule\Livewire\Home;
use App\Modules\CommunicationsModule\Livewire\ListNews;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin/communications')->name('communications.')->group(function () {
    // Moderación de contenido
    Route::get('/moderation', [ContentModerationController::class, 'index'])->name('moderation.index');
    Route::post('/moderation/approve', [ContentModerationController::class, 'approve'])->name('moderation.approve');
    Route::post('/moderation/reject', [ContentModerationController::class, 'reject'])->name('moderation.reject');
    Route::post('/moderation/archive', [ContentModerationController::class, 'archive'])->name('moderation.archive');
    Route::post('/moderation/submit-review', [ContentModerationController::class, 'submitForReview'])->name('moderation.submit-review');

    // Noticias
    Route::get('/news', ListNews::class)->name('news.index');
    Route::get('/news/create', CreateNews::class)->name('news.create');
    Route::get('/news/{news}/edit', EditNews::class)->name('news.edit');

    // Categorías
    Route::resource('categories', CategoryController::class)->names([
        'index' => 'admin.categories.index',
        'create' => 'admin.categories.create',
        'store' => 'admin.categories.store',
        'show' => 'admin.categories.show',
        'edit' => 'admin.categories.edit',
        'update' => 'admin.categories.update',
        'destroy' => 'admin.categories.destroy',
    ]);

    // Tags
    Route::resource('tags', TagController::class)->names([
        'index' => 'admin.tags.index',
        'create' => 'admin.tags.create',
        'store' => 'admin.tags.store',
        'show' => 'admin.tags.show',
        'edit' => 'admin.tags.edit',
        'update' => 'admin.tags.update',
        'destroy' => 'admin.tags.destroy',
    ]);

    // Encuestas
    Route::get('polls', \App\Modules\CommunicationsModule\Livewire\ListPolls::class)->name('polls.index');
    Route::get('polls/create', \App\Modules\CommunicationsModule\Livewire\CreatePoll::class)->name('polls.create');
    
    // Reconocimientos
    Route::get('/shoutouts', \App\Modules\CommunicationsModule\Livewire\ListShoutouts::class)->name('shoutouts.index');
    Route::get('/shoutouts/create', \App\Modules\CommunicationsModule\Livewire\CreateShoutout::class)->name('shoutouts.create');
    Route::get('/shoutouts/{shoutout}/edit', \App\Modules\CommunicationsModule\Livewire\EditShoutout::class)->name('shoutouts.edit');
});

// Rutas públicas para interacciones sociales
Route::middleware('auth')->name('communications.')->group(function () {
    // Comentarios en noticias
    Route::post('news/{news}/comments', [CommentController::class, 'store'])
        ->name('comments.store');

    // Reacciones en shoutouts
    Route::post('shoutouts/{shoutout}/reactions', [ReactionController::class, 'store'])
        ->name('reactions.store');

    // Página principal de comunicaciones (landing page)
    Route::get('/', Home::class)
        ->name('home');
});
