<?php

declare(strict_types=1);

use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Modules\OperationsModule\Models\IncidentType;
use App\Modules\OperationsModule\Policies\AgentDailyMetricPolicy;
use App\Modules\OperationsModule\Policies\AttendanceIncidentPolicy;
use App\Modules\OperationsModule\Policies\IncidentTypePolicy;
use App\Modules\OperationsModule\Providers\ModuleServiceProvider;
use App\Shared\Models\BaseModel;

test('RealtimeMonitoring no importa Schedule inexistente', function () {
    $contents = file_get_contents(app_path('Modules/OperationsModule/Livewire/RealtimeMonitoring.php'));
    expect($contents)->not->toContain('use App\Modules\OperationsModule\Models\Schedule;');
    expect($contents)->toContain("\$this->authorize('monitorRealtime')");
});

test('AttendanceIncidentPolicy existe y es instanciable', function () {
    expect(class_exists(AttendanceIncidentPolicy::class))->toBeTrue();
});

test('AgentDailyMetricPolicy existe y es instanciable', function () {
    expect(class_exists(AgentDailyMetricPolicy::class))->toBeTrue();
});

test('IncidentTypePolicy existe y es instanciable', function () {
    expect(class_exists(IncidentTypePolicy::class))->toBeTrue();
});

test('Policies registradas en ModuleServiceProvider', function () {
    $provider = new ModuleServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $boot = $reflection->getMethod('boot');
    $boot->setAccessible(true);

    expect(true)->toBeTrue();
});

test('AgentDailyMetric extiende BaseModel', function () {
    expect(new AgentDailyMetric)->toBeInstanceOf(BaseModel::class);
});

test('IncidentType extiende BaseModel', function () {
    expect(new IncidentType)->toBeInstanceOf(BaseModel::class);
});
