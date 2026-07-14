<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Providers;

use App\Modules\QualityModule\Console\Commands\SeedQualityData;
use App\Modules\QualityModule\Events\CalibrationCreated;
use App\Modules\QualityModule\Events\EvaluationCreated;
use App\Modules\QualityModule\Listeners\SendEvaluationNotification;
use App\Modules\QualityModule\Listeners\UpdateQueueScoreAverages;
use App\Modules\QualityModule\Livewire\CriteriaList;
use App\Modules\QualityModule\Livewire\EvaluationIndex;
use App\Modules\QualityModule\Livewire\QueueList;
use App\Modules\QualityModule\Models\CalibrationLog;
use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Models\Feedback;
use App\Modules\QualityModule\Observers\EvaluationObserver;
use App\Modules\QualityModule\Policies\CalibrationPolicy;
use App\Modules\QualityModule\Policies\CriteriaPolicy;
use App\Modules\QualityModule\Policies\EvaluationPolicy;
use App\Modules\QualityModule\Policies\FeedbackPolicy;
use App\Modules\QualityModule\Repositories\EloquentCriteriaRepository;
use App\Modules\QualityModule\Repositories\EloquentEvaluationRepository;
use App\Shared\Contracts\Quality\CriteriaRepositoryInterface;
use App\Shared\Contracts\Quality\EvaluationRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            EvaluationRepositoryInterface::class,
            EloquentEvaluationRepository::class,
        );

        $this->app->singleton(
            CriteriaRepositoryInterface::class,
            EloquentCriteriaRepository::class,
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'quality');

        if (file_exists(__DIR__.'/../Routes/web.php')) {
            Route::middleware(['web', 'auth', 'verified'])
                ->prefix('quality')
                ->name('quality.')
                ->group(__DIR__.'/../Routes/web.php');
        }

        Evaluation::observe(EvaluationObserver::class);

        $this->registerLivewireComponents();
        $this->registerPolicies();
        $this->registerEventListeners();
        $this->registerCommands();
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('quality.evaluation-index', EvaluationIndex::class);
        Livewire::component('quality.criteria-list', CriteriaList::class);
        Livewire::component('quality.queue-list', QueueList::class);
    }

    private function registerPolicies(): void
    {
        Gate::policy(Evaluation::class, EvaluationPolicy::class);
        Gate::policy(Criteria::class, CriteriaPolicy::class);
        Gate::policy(Feedback::class, FeedbackPolicy::class);
        Gate::policy(CalibrationLog::class, CalibrationPolicy::class);
    }

    private function registerEventListeners(): void
    {
        Event::listen(
            EvaluationCreated::class,
            SendEvaluationNotification::class,
        );
        Event::listen(
            EvaluationCreated::class,
            UpdateQueueScoreAverages::class,
        );
        Event::listen(
            CalibrationCreated::class,
            UpdateQueueScoreAverages::class,
        );
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SeedQualityData::class,
            ]);
        }
    }
}
