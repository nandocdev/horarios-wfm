<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Providers;

use App\Modules\QualityModule\Console\Commands\SeedQualityData;
use App\Modules\QualityModule\Events\CalibrationCreated;
use App\Modules\QualityModule\Events\EvaluationCreated;
use App\Modules\QualityModule\Listeners\SendEvaluationNotification;
use App\Modules\QualityModule\Listeners\UpdateQueueScoreAverages;
use App\Modules\QualityModule\Models\Evaluation;
use App\Modules\QualityModule\Observers\EvaluationObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void {}

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

        $this->registerEventListeners();
        $this->registerCommands();
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
