<?php

declare(strict_types=1);

use App\Src\Platform\Presentation\Http\Controllers\AuditExportController;
use App\Src\Platform\Presentation\Http\Controllers\CategoryController;
use App\Src\Platform\Presentation\Http\Controllers\CommentController;
use App\Src\Platform\Presentation\Http\Controllers\ContentModerationController;
use App\Src\Platform\Presentation\Http\Controllers\ReactionController;
use App\Src\Platform\Presentation\Http\Controllers\TagController;
use App\Src\Platform\Presentation\Livewire\CreateNews;
use App\Src\Platform\Presentation\Livewire\CreatePoll;
use App\Src\Platform\Presentation\Livewire\CreateShoutout;
use App\Src\Platform\Presentation\Livewire\EditNews;
use App\Src\Platform\Presentation\Livewire\EditShoutout;
use App\Src\Platform\Presentation\Livewire\Home;
use App\Src\Platform\Presentation\Livewire\ListAuditLogs;
use App\Src\Platform\Presentation\Livewire\ListNews;
use App\Src\Platform\Presentation\Livewire\ListPolls;
use App\Src\Platform\Presentation\Livewire\ListShoutouts;
use App\Src\Platform\Presentation\Policies\AuditLogPolicy;
use App\Src\Platform\Presentation\Policies\CategoryPolicy;
use App\Src\Platform\Presentation\Policies\CommentPolicy;
use App\Src\Platform\Presentation\Policies\ContentModerationPolicy;
use App\Src\Platform\Presentation\Policies\ReactionPolicy;
use App\Src\Platform\Presentation\Policies\TagPolicy;
use Illuminate\Support\Facades\Route;

// ── Audit Module Routes ──
Route::middleware(['web', 'auth', 'verified'])->prefix('admin/platform/audit')->name('platform.audit.')->group(function () {
    Route::get('/', ListAuditLogs::class)
        ->name('index')
        ->can('viewAny', AuditLogPolicy::class);

    Route::get('/export', [AuditExportController::class, 'export'])
        ->name('export')
        ->can('export', AuditLogPolicy::class);
});

// ── Communications Module Routes ──
Route::middleware(['web', 'auth', 'verified'])->prefix('admin/platform/communications')->name('platform.communications.')->group(function () {
    Route::get('/', Home::class)->name('home');

    Route::get('/moderation', [ContentModerationController::class, 'index'])
        ->name('moderation.index')
        ->can('viewPending', ContentModerationPolicy::class);
    Route::post('/moderation/approve', [ContentModerationController::class, 'approve'])->name('moderation.approve');
    Route::post('/moderation/reject', [ContentModerationController::class, 'reject'])->name('moderation.reject');
    Route::post('/moderation/archive', [ContentModerationController::class, 'archive'])->name('moderation.archive');
    Route::post('/moderation/submit-review', [ContentModerationController::class, 'submitForReview'])->name('moderation.submit-review');

    Route::get('/news', ListNews::class)->name('news.index');
    Route::get('/news/create', CreateNews::class)->name('news.create');
    Route::get('/news/{news}/edit', EditNews::class)->name('news.edit');

    Route::get('/polls', ListPolls::class)->name('polls.index');
    Route::get('/polls/create', CreatePoll::class)->name('polls.create');

    Route::get('/shoutouts', ListShoutouts::class)->name('shoutouts.index');
    Route::get('/shoutouts/create', CreateShoutout::class)->name('shoutouts.create');
    Route::get('/shoutouts/{shoutout}/edit', EditShoutout::class)->name('shoutouts.edit');

    Route::resource('categories', CategoryController::class)->names([
        'index' => 'admin.categories.index', 'create' => 'admin.categories.create',
        'store' => 'admin.categories.store', 'show' => 'admin.categories.show',
        'edit' => 'admin.categories.edit', 'update' => 'admin.categories.update',
        'destroy' => 'admin.categories.destroy',
    ]);

    Route::resource('tags', TagController::class)->names([
        'index' => 'admin.tags.index', 'create' => 'admin.tags.create',
        'store' => 'admin.tags.store', 'show' => 'admin.tags.show',
        'edit' => 'admin.tags.edit', 'update' => 'admin.tags.update',
        'destroy' => 'admin.tags.destroy',
    ]);
});

// Social interaction routes (inside prefix to inherit /admin/platform/communications/)
Route::middleware(['web', 'auth', 'verified'])
    ->prefix('admin/platform/communications')
    ->name('platform.communications.')
    ->group(function () {
        Route::post('news/{news}/comments', [CommentController::class, 'store'])
            ->name('comments.store')
            ->can('create', CommentPolicy::class);

        Route::post('shoutouts/{shoutout}/reactions', [ReactionController::class, 'store'])
            ->name('reactions.store')
            ->can('create', ReactionPolicy::class);
    });
