<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Providers;

use App\Modules\CommunicationsModule\Events\CommentCreated;
use App\Modules\CommunicationsModule\Events\MentionCreated;
use App\Modules\CommunicationsModule\Events\ReactionAdded;
use App\Modules\CommunicationsModule\Listeners\SendCommentNotificationListener;
use App\Modules\CommunicationsModule\Listeners\SendLeaveRequestCreatedNotification;
use App\Modules\CommunicationsModule\Listeners\SendLeaveRequestDecisionNotification;
use App\Modules\CommunicationsModule\Listeners\SendMentionNotificationListener;
use App\Modules\CommunicationsModule\Listeners\SendReactionNotificationListener;
use App\Modules\CommunicationsModule\Listeners\SendScheduleAssignmentUpdatedNotification;
use App\Modules\CommunicationsModule\Listeners\SendShiftSwapApprovedNotification;
use App\Modules\CommunicationsModule\Listeners\SendShiftSwapReceivedNotification;
use App\Modules\CommunicationsModule\Listeners\SendWeeklySchedulePublishedNotification;
use App\Modules\CommunicationsModule\Livewire\CreateNews;
use App\Modules\CommunicationsModule\Livewire\EditNews;
use App\Modules\CommunicationsModule\Livewire\Home;
use App\Modules\CommunicationsModule\Livewire\ManageContent;
use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CommunicationsModule\Models\Comment;
use App\Modules\CommunicationsModule\Models\Mention;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Notification;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\Reaction;
use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Modules\CommunicationsModule\Models\Tag;
use App\Modules\CommunicationsModule\Observers\CategoryObserver;
use App\Modules\CommunicationsModule\Observers\CommentObserver;
use App\Modules\CommunicationsModule\Observers\MentionObserver;
use App\Modules\CommunicationsModule\Observers\NewsObserver;
use App\Modules\CommunicationsModule\Observers\NotificationObserver;
use App\Modules\CommunicationsModule\Observers\PollObserver;
use App\Modules\CommunicationsModule\Observers\ReactionObserver;
use App\Modules\CommunicationsModule\Observers\ShoutoutObserver;
use App\Modules\CommunicationsModule\Observers\TagObserver;
use App\Modules\CommunicationsModule\Policies\CategoryPolicy;
use App\Modules\CommunicationsModule\Policies\CommentPolicy;
use App\Modules\CommunicationsModule\Policies\ContentModerationPolicy;
use App\Modules\CommunicationsModule\Policies\MentionPolicy;
use App\Modules\CommunicationsModule\Policies\NewsPolicy;
use App\Modules\CommunicationsModule\Policies\NotificationPolicy;
use App\Modules\CommunicationsModule\Policies\PollPolicy;
use App\Modules\CommunicationsModule\Policies\ReactionPolicy;
use App\Modules\CommunicationsModule\Policies\ShoutoutPolicy;
use App\Modules\CommunicationsModule\Policies\TagPolicy;
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

/**
 * Proveedor de servicios para el módulo de comunicaciones.
 * Registra rutas, vistas, políticas y componentes dinámicos.
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Registro del módulo.
     */
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
        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware('web')->group(__DIR__.'/../Routes/web.php');
        }
    }

    private function registerObservers(): void
    {
        News::observe(NewsObserver::class);
        Poll::observe(PollObserver::class);
        Shoutout::observe(ShoutoutObserver::class);
        Category::observe(CategoryObserver::class);
        Tag::observe(TagObserver::class);
        Comment::observe(CommentObserver::class);
        Reaction::observe(ReactionObserver::class);
        Mention::observe(MentionObserver::class);
        Notification::observe(NotificationObserver::class);
    }

    private function registerPolicies(): void
    {
        Gate::policy(News::class, NewsPolicy::class);
        Gate::policy(Poll::class, PollPolicy::class);
        Gate::policy(Shoutout::class, ShoutoutPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(Reaction::class, ReactionPolicy::class);
        Gate::policy(Mention::class, MentionPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);

        // Policy para moderación de contenido
        Gate::policy(ContentModerationPolicy::class, ContentModerationPolicy::class);
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('communications.manage-content', ManageContent::class);
        Livewire::component('communications.create-news', CreateNews::class);
        Livewire::component('communications.edit-news', EditNews::class);
        Livewire::component('communications.home', Home::class);
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            CommentCreated::class,
            SendCommentNotificationListener::class
        );

        Event::listen(
            ReactionAdded::class,
            SendReactionNotificationListener::class
        );

        Event::listen(
            MentionCreated::class,
            SendMentionNotificationListener::class
        );

        Event::listen(WeeklySchedulePublished::class, SendWeeklySchedulePublishedNotification::class);
        Event::listen(ScheduleAssignmentUpdated::class, SendScheduleAssignmentUpdatedNotification::class);
        Event::listen(LeaveRequestCreated::class, SendLeaveRequestCreatedNotification::class);
        Event::listen(LeaveRequestDecision::class, SendLeaveRequestDecisionNotification::class);
        Event::listen(ShiftSwapApproved::class, SendShiftSwapApprovedNotification::class);
        Event::listen(ShiftSwapRequested::class, SendShiftSwapReceivedNotification::class);
    }

    private function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'communications');
    }

    public function register(): void
    {
        // Implementación futura de servicios compartidos
    }
}
