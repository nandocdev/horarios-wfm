<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Providers;

use App\Shared\Events\LeaveRequestCreated;
use App\Shared\Events\LeaveRequestDecision;
use App\Shared\Events\ScheduleAssignmentUpdated;
use App\Shared\Events\ShiftSwapApproved;
use App\Shared\Events\ShiftSwapRequested;
use App\Shared\Events\WeeklySchedulePublished;
use App\Src\Platform\Domain\Events\CommentCreated;
use App\Src\Platform\Domain\Events\MentionCreated;
use App\Src\Platform\Domain\Events\ReactionAdded;
use App\Src\Platform\Domain\Repositories\AuditLogRepositoryInterface;
use App\Src\Platform\Domain\Repositories\CategoryRepositoryInterface;
use App\Src\Platform\Domain\Repositories\CommentRepositoryInterface;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Domain\Repositories\MentionRepositoryInterface;
use App\Src\Platform\Domain\Repositories\NewsRepositoryInterface;
use App\Src\Platform\Domain\Repositories\PollRepositoryInterface;
use App\Src\Platform\Domain\Repositories\ReactionRepositoryInterface;
use App\Src\Platform\Domain\Repositories\ShoutoutRepositoryInterface;
use App\Src\Platform\Domain\Repositories\TagRepositoryInterface;
use App\Src\Platform\Infrastructure\Console\AutoArchiveContentCommand;
use App\Src\Platform\Infrastructure\Console\AuditPruneCommand;
use App\Src\Platform\Infrastructure\Console\PublishScheduledContentCommand;
use App\Src\Platform\Infrastructure\Console\SendAutomaticNewsletterCommand;
use App\Src\Platform\Infrastructure\Console\SendExpiredPollRemindersCommand;
use App\Src\Platform\Infrastructure\Integrations\S3StorageAdapter;
use App\Src\Platform\Infrastructure\Listeners\AuditLeaveRequestCreatedListener;
use App\Src\Platform\Infrastructure\Listeners\AuditLeaveRequestDecisionListener;
use App\Src\Platform\Infrastructure\Listeners\AuditShiftSwapApprovedListener;
use App\Src\Platform\Infrastructure\Listeners\AuditWeeklySchedulePublishedListener;
use App\Src\Platform\Infrastructure\Listeners\SendCommentNotificationListener;
use App\Src\Platform\Infrastructure\Listeners\SendLeaveRequestCreatedNotification;
use App\Src\Platform\Infrastructure\Listeners\SendLeaveRequestDecisionNotification;
use App\Src\Platform\Infrastructure\Listeners\SendMentionNotificationListener;
use App\Src\Platform\Infrastructure\Listeners\SendReactionNotificationListener;
use App\Src\Platform\Infrastructure\Listeners\SendScheduleAssignmentUpdatedNotification;
use App\Src\Platform\Infrastructure\Listeners\SendShiftSwapApprovedNotification;
use App\Src\Platform\Infrastructure\Listeners\SendShiftSwapReceivedNotification;
use App\Src\Platform\Infrastructure\Listeners\SendWeeklySchedulePublishedNotification;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use App\Src\Platform\Infrastructure\Notifications\EmailNotificationChannel;
use App\Src\Platform\Infrastructure\Persistence\EloquentAuditLogRepository;
use App\Src\Platform\Infrastructure\Persistence\EloquentCategoryRepository;
use App\Src\Platform\Infrastructure\Persistence\EloquentCommentRepository;
use App\Src\Platform\Infrastructure\Persistence\EloquentInAppNotificationRepository;
use App\Src\Platform\Infrastructure\Persistence\EloquentMentionRepository;
use App\Src\Platform\Infrastructure\Persistence\EloquentNewsRepository;
use App\Src\Platform\Infrastructure\Persistence\EloquentPollRepository;
use App\Src\Platform\Infrastructure\Persistence\EloquentReactionRepository;
use App\Src\Platform\Infrastructure\Persistence\EloquentShoutoutRepository;
use App\Src\Platform\Infrastructure\Persistence\EloquentTagRepository;
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
use App\Src\Platform\Presentation\Policies\InAppNotificationPolicy;
use App\Src\Platform\Presentation\Policies\MentionPolicy;
use App\Src\Platform\Presentation\Policies\NewsPolicy;
use App\Src\Platform\Presentation\Policies\PollPolicy;
use App\Src\Platform\Presentation\Policies\ReactionPolicy;
use App\Src\Platform\Presentation\Policies\ShoutoutPolicy;
use App\Src\Platform\Presentation\Policies\TagPolicy;
use App\Src\Shared\Infrastructure\Events\DomainEventDispatcher;
use App\Src\Shared\Infrastructure\Events\LaravelDomainEventDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class PlatformServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->singleton(DomainEventDispatcher::class, fn($app) => new LaravelDomainEventDispatcher($app->make(Dispatcher::class)));

        // Audit repositories
        $this->app->bind(AuditLogRepositoryInterface::class, EloquentAuditLogRepository::class);

        // Communications repositories
        $this->app->bind(NewsRepositoryInterface::class, EloquentNewsRepository::class);
        $this->app->bind(PollRepositoryInterface::class, EloquentPollRepository::class);
        $this->app->bind(ShoutoutRepositoryInterface::class, EloquentShoutoutRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, EloquentCommentRepository::class);
        $this->app->bind(ReactionRepositoryInterface::class, EloquentReactionRepository::class);
        $this->app->bind(MentionRepositoryInterface::class, EloquentMentionRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(TagRepositoryInterface::class, EloquentTagRepository::class);
        $this->app->bind(InAppNotificationRepositoryInterface::class, EloquentInAppNotificationRepository::class);

        // Infrastructure singletons
        $this->app->singleton(S3StorageAdapter::class, fn() => new S3StorageAdapter());
        $this->app->singleton(EmailNotificationChannel::class);
        $this->app->singleton(BroadcastNotificationChannel::class);
    }

    public function boot(): void {
        $this->loadViews();
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerLivewireComponents();
        $this->registerEventListeners();
        $this->loadTranslations();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    private function loadViews(): void {
        $viewsPath = __DIR__ . '/../../Presentation/Resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'platform');
        }
    }

    private function registerRoutes(): void {
        $routesPath = __DIR__ . '/../../Presentation/Routes/web.php';
        if (file_exists($routesPath)) {
            Route::middleware('web')->group($routesPath);
        }
    }

    private function registerPolicies(): void {
        Gate::policy(AuditLogPolicy::class, AuditLogPolicy::class);
        Gate::policy(NewsPolicy::class, NewsPolicy::class);
        Gate::policy(PollPolicy::class, PollPolicy::class);
        Gate::policy(ShoutoutPolicy::class, ShoutoutPolicy::class);
        Gate::policy(CommentPolicy::class, CommentPolicy::class);
        Gate::policy(ReactionPolicy::class, ReactionPolicy::class);
        Gate::policy(MentionPolicy::class, MentionPolicy::class);
        Gate::policy(CategoryPolicy::class, CategoryPolicy::class);
        Gate::policy(TagPolicy::class, TagPolicy::class);
        Gate::policy(InAppNotificationPolicy::class, InAppNotificationPolicy::class);
        Gate::policy(ContentModerationPolicy::class, ContentModerationPolicy::class);
    }

    private function registerLivewireComponents(): void {
        // Audit
        Livewire::component('platform.list-audit-logs', ListAuditLogs::class);

        // Communications
        Livewire::component('platform.communications.home', Home::class);
        Livewire::component('platform.communications.list-news', ListNews::class);
        Livewire::component('platform.communications.create-news', CreateNews::class);
        Livewire::component('platform.communications.edit-news', EditNews::class);
        Livewire::component('platform.communications.list-polls', ListPolls::class);
        Livewire::component('platform.communications.create-poll', CreatePoll::class);
        Livewire::component('platform.communications.list-shoutouts', ListShoutouts::class);
        Livewire::component('platform.communications.create-shoutout', CreateShoutout::class);
        Livewire::component('platform.communications.edit-shoutout', EditShoutout::class);
    }

    private function registerEventListeners(): void {
        // Internal domain events
        Event::listen(CommentCreated::class, SendCommentNotificationListener::class);
        Event::listen(ReactionAdded::class, SendReactionNotificationListener::class);
        Event::listen(MentionCreated::class, SendMentionNotificationListener::class);

        // Audit logging
        Event::listen(WeeklySchedulePublished::class, AuditWeeklySchedulePublishedListener::class);
        Event::listen(LeaveRequestCreated::class, AuditLeaveRequestCreatedListener::class);
        Event::listen(LeaveRequestDecision::class, AuditLeaveRequestDecisionListener::class);
        Event::listen(ShiftSwapApproved::class, AuditShiftSwapApprovedListener::class);

        // Cross-module user notifications
        Event::listen(WeeklySchedulePublished::class, SendWeeklySchedulePublishedNotification::class);
        Event::listen(LeaveRequestCreated::class, SendLeaveRequestCreatedNotification::class);
        Event::listen(LeaveRequestDecision::class, SendLeaveRequestDecisionNotification::class);
        Event::listen(ShiftSwapApproved::class, SendShiftSwapApprovedNotification::class);
        Event::listen(ShiftSwapRequested::class, SendShiftSwapReceivedNotification::class);
        Event::listen(ScheduleAssignmentUpdated::class, SendScheduleAssignmentUpdatedNotification::class);
    }

    private function loadTranslations(): void {
        $path = __DIR__ . '/../Resources/lang';
        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, 'platform');
        }
    }

    private function registerCommands(): void {
        $this->commands([
            AuditPruneCommand::class,
            AutoArchiveContentCommand::class,
            PublishScheduledContentCommand::class,
            SendAutomaticNewsletterCommand::class,
            SendExpiredPollRemindersCommand::class,
        ]);
    }
}
