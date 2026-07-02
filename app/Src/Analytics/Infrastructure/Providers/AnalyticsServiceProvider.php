<?php

declare(strict_types=1);

namespace App\Src\Analytics\Infrastructure\Providers;

use App\Src\Analytics\Domain\Repositories\AnalyticsRepositoryInterface;
use App\Src\Analytics\Infrastructure\Console\AggregateMetricsCommand;
use App\Src\Analytics\Infrastructure\Persistence\EloquentAnalyticsRepository;
use Illuminate\Support\ServiceProvider;

final class AnalyticsServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(AnalyticsRepositoryInterface::class, EloquentAnalyticsRepository::class);
    }

    public function boot(): void {
        if ($this->app->runningInConsole()) {
            $this->commands([AggregateMetricsCommand::class]);
        }
    }
}
