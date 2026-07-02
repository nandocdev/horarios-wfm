<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Providers;

use App\Src\Platform\Domain\Repositories\AuditLogRepositoryInterface;
use App\Src\Platform\Infrastructure\Persistence\EloquentAuditLogRepository;
use App\Src\Platform\Infrastructure\Integrations\S3StorageAdapter;
use App\Src\Platform\Infrastructure\Notifications\EmailNotificationChannel;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use App\Src\Shared\Infrastructure\Events\DomainEventDispatcher;
use App\Src\Shared\Infrastructure\Events\LaravelDomainEventDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class PlatformServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->singleton(DomainEventDispatcher::class, function ($app) {
            return new LaravelDomainEventDispatcher($app->make(Dispatcher::class));
        });

        $this->app->bind(AuditLogRepositoryInterface::class, EloquentAuditLogRepository::class);

        $this->app->singleton(S3StorageAdapter::class, function () {
            return new S3StorageAdapter();
        });

        $this->app->singleton(EmailNotificationChannel::class);
        $this->app->singleton(BroadcastNotificationChannel::class);
    }

    public function boot(): void {
        $this->loadTranslations();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    private function loadTranslations(): void {
        $path = __DIR__ . '/../Resources/lang';
        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, 'platform');
        }
    }

    private function registerCommands(): void {
        $this->commands([
            \App\Src\Platform\Infrastructure\Console\AuditPruneCommand::class,
        ]);
    }
}
