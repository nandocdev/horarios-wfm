<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Providers;

use App\Modules\CommunicationsModule\Domain\Repositories\NewsRepository;
use App\Modules\CommunicationsModule\Domain\Repositories\PollRepository;
use App\Modules\CommunicationsModule\Domain\Repositories\ShoutoutRepository;
use App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\Policies\NewsPolicy;
use App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\Repositories\NewsEloquentRepository;
use App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\Repositories\PollEloquentRepository;
use App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\Repositories\ShoutoutEloquentRepository;
use App\Shared\Events\LeaveRequestCreated;
use App\Shared\Events\LeaveRequestDecision;
use App\Shared\Events\ScheduleAssignmentUpdated;
use App\Shared\Events\ShiftSwapApproved;
use App\Shared\Events\ShiftSwapRequested;
use App\Shared\Events\WeeklySchedulePublished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NewsRepository::class, NewsEloquentRepository::class);
        $this->app->bind(ShoutoutRepository::class, ShoutoutEloquentRepository::class);
        $this->app->bind(PollRepository::class, PollEloquentRepository::class);
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerObservers();
        $this->registerPolicies();
        $this->registerEventListeners();
        $this->registerLivewireComponents();
        $this->loadViews();
    }

    private function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(__DIR__.'/../Routes/web.php');
    }

    private function registerObservers(): void
    {
        // Existing observers — cache invalidation only, keep as-is
        \App\Modules\CommunicationsModule\Models\News::observe(\App\Modules\CommunicationsModule\Observers\NewsObserver::class);
        \App\Modules\CommunicationsModule\Models\Poll::observe(\App\Modules\CommunicationsModule\Observers\PollObserver::class);
        \App\Modules\CommunicationsModule\Models\Shoutout::observe(\App\Modules\CommunicationsModule\Observers\ShoutoutObserver::class);
        \App\Modules\CommunicationsModule\Models\Category::observe(\App\Modules\CommunicationsModule\Observers\CategoryObserver::class);
        \App\Modules\CommunicationsModule\Models\Tag::observe(\App\Modules\CommunicationsModule\Observers\TagObserver::class);
        \App\Modules\CommunicationsModule\Models\Comment::observe(\App\Modules\CommunicationsModule\Observers\CommentObserver::class);
        \App\Modules\CommunicationsModule\Models\Reaction::observe(\App\Modules\CommunicationsModule\Observers\ReactionObserver::class);
        \App\Modules\CommunicationsModule\Models\Mention::observe(\App\Modules\CommunicationsModule\Observers\MentionObserver::class);
        \App\Modules\CommunicationsModule\Models\Notification::observe(\App\Modules\CommunicationsModule\Observers\NotificationObserver::class);
    }

    private function registerPolicies(): void
    {
        // New policies
        Gate::policy(\App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\NewsModel::class, NewsPolicy::class);

        // Existing policies (keep for old models)
        Gate::policy(\App\Modules\CommunicationsModule\Models\News::class, \App\Modules\CommunicationsModule\Policies\NewsPolicy::class);
        Gate::policy(\App\Modules\CommunicationsModule\Models\Poll::class, \App\Modules\CommunicationsModule\Policies\PollPolicy::class);
        Gate::policy(\App\Modules\CommunicationsModule\Models\Shoutout::class, \App\Modules\CommunicationsModule\Policies\ShoutoutPolicy::class);
        Gate::policy(\App\Modules\CommunicationsModule\Models\Category::class, \App\Modules\CommunicationsModule\Policies\CategoryPolicy::class);
        Gate::policy(\App\Modules\CommunicationsModule\Models\Tag::class, \App\Modules\CommunicationsModule\Policies\TagPolicy::class);
        Gate::policy(\App\Modules\CommunicationsModule\Models\Comment::class, \App\Modules\CommunicationsModule\Policies\CommentPolicy::class);
        Gate::policy(\App\Modules\CommunicationsModule\Models\Reaction::class, \App\Modules\CommunicationsModule\Policies\ReactionPolicy::class);
        Gate::policy(\App\Modules\CommunicationsModule\Models\Mention::class, \App\Modules\CommunicationsModule\Policies\MentionPolicy::class);
        Gate::policy(\App\Modules\CommunicationsModule\Models\Notification::class, \App\Modules\CommunicationsModule\Policies\NotificationPolicy::class);
        Gate::policy(\App\Modules\CommunicationsModule\Policies\ContentModerationPolicy::class, \App\Modules\CommunicationsModule\Policies\ContentModerationPolicy::class);
    }

    private function registerEventListeners(): void
    {
        Event::listen(\App\Modules\CommunicationsModule\Events\CommentCreated::class, \App\Modules\CommunicationsModule\Listeners\SendCommentNotificationListener::class);
        Event::listen(\App\Modules\CommunicationsModule\Events\ReactionAdded::class, \App\Modules\CommunicationsModule\Listeners\SendReactionNotificationListener::class);
        Event::listen(\App\Modules\CommunicationsModule\Events\MentionCreated::class, \App\Modules\CommunicationsModule\Listeners\SendMentionNotificationListener::class);

        Event::listen(WeeklySchedulePublished::class, \App\Modules\CommunicationsModule\Listeners\SendWeeklySchedulePublishedNotification::class);
        Event::listen(ScheduleAssignmentUpdated::class, \App\Modules\CommunicationsModule\Listeners\SendScheduleAssignmentUpdatedNotification::class);
        Event::listen(LeaveRequestCreated::class, \App\Modules\CommunicationsModule\Listeners\SendLeaveRequestCreatedNotification::class);
        Event::listen(LeaveRequestDecision::class, \App\Modules\CommunicationsModule\Listeners\SendLeaveRequestDecisionNotification::class);
        Event::listen(ShiftSwapApproved::class, \App\Modules\CommunicationsModule\Listeners\SendShiftSwapApprovedNotification::class);
        Event::listen(ShiftSwapRequested::class, \App\Modules\CommunicationsModule\Listeners\SendShiftSwapReceivedNotification::class);
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('communications.home', \App\Modules\CommunicationsModule\Livewire\Home::class);
        Livewire::component('communications.list-news', \App\Modules\CommunicationsModule\Livewire\ListNews::class);
        Livewire::component('communications.create-news', \App\Modules\CommunicationsModule\Livewire\CreateNews::class);
        Livewire::component('communications.edit-news', \App\Modules\CommunicationsModule\Livewire\EditNews::class);
        Livewire::component('communications.list-polls', \App\Modules\CommunicationsModule\Livewire\ListPolls::class);
        Livewire::component('communications.create-poll', \App\Modules\CommunicationsModule\Livewire\CreatePoll::class);
        Livewire::component('communications.list-shoutouts', \App\Modules\CommunicationsModule\Livewire\ListShoutouts::class);
        Livewire::component('communications.create-shoutout', \App\Modules\CommunicationsModule\Livewire\CreateShoutout::class);
        Livewire::component('communications.edit-shoutout', \App\Modules\CommunicationsModule\Livewire\EditShoutout::class);
    }

    private function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'communications');
    }
}
