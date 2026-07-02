<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Infrastructure\Providers;

use App\Src\TimeAndAttendance\Domain\Repositories\AttendanceRepositoryInterface;
use App\Src\TimeAndAttendance\Infrastructure\Integrations\IdentityValidatorInterface;
use App\Src\TimeAndAttendance\Infrastructure\Integrations\NullIdentityValidator;
use App\Src\TimeAndAttendance\Infrastructure\Persistence\EloquentAttendanceRepository;
use Illuminate\Support\ServiceProvider;

final class TimeAndAttendanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AttendanceRepositoryInterface::class, EloquentAttendanceRepository::class);
        $this->app->bind(IdentityValidatorInterface::class, NullIdentityValidator::class);
    }
}
