<?php

declare(strict_types=1);

use App\Modules\WfmModule\Models\ApprovedIntradayPeriod;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Policies\ApprovedIntradayPeriodPolicy;
use App\Modules\WfmModule\Policies\LeaveRequestPolicy;
use App\Modules\WfmModule\Policies\ScheduleExceptionPolicy;
use App\Modules\WfmModule\Policies\ShiftSwapRequestPolicy;
use App\Modules\WfmModule\Providers\ModuleServiceProvider;

test('LeaveRequest policy registrada en ModuleServiceProvider', function () {
    $provider = new ModuleServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('policies');
    $property->setAccessible(true);
    $policies = $property->getValue($provider);

    expect($policies[LeaveRequest::class] ?? null)->toBe(LeaveRequestPolicy::class);
});

test('ShiftSwapRequest policy registrada en ModuleServiceProvider', function () {
    $provider = new ModuleServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('policies');
    $property->setAccessible(true);
    $policies = $property->getValue($provider);

    expect($policies[ShiftSwapRequest::class] ?? null)->toBe(ShiftSwapRequestPolicy::class);
});

test('ScheduleException policy registrada en ModuleServiceProvider', function () {
    $provider = new ModuleServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('policies');
    $property->setAccessible(true);
    $policies = $property->getValue($provider);

    expect($policies[ScheduleException::class] ?? null)->toBe(ScheduleExceptionPolicy::class);
});

test('ApprovedIntradayPeriod policy registrada en ModuleServiceProvider', function () {
    $provider = new ModuleServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $property = $reflection->getProperty('policies');
    $property->setAccessible(true);
    $policies = $property->getValue($provider);

    expect($policies[ApprovedIntradayPeriod::class] ?? null)->toBe(ApprovedIntradayPeriodPolicy::class);
});

test('ShiftSwapForm archivo existe', function () {
    expect(file_exists(app_path('Modules/WfmModule/Livewire/Forms/ShiftSwapForm.php')))->toBeTrue();
});

test('ScheduleRepositoryInterface tiene use import en ModuleServiceProvider', function () {
    $contents = file_get_contents(app_path('Modules/WfmModule/Providers/ModuleServiceProvider.php'));
    expect($contents)->toContain('use App\Shared\Contracts\Schedules\ScheduleRepositoryInterface');
    expect($contents)->toContain('use App\Modules\WfmModule\Repositories\EloquentScheduleRepository');
});
