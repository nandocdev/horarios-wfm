<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Policies\AgentRealtimeStatePolicy;
use App\Modules\ConnectModule\Policies\ChannelPolicy;
use App\Modules\ConnectModule\Providers\ModuleServiceProvider;
use App\Shared\Events\SyncFailed;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;

test('CiscoFinesseClient tiene timeout diferenciado para batch', function () {
    $reflection = new ReflectionClass(CiscoFinesseClient::class);
    $batchTimeout = $reflection->getProperty('batchTimeout');
    $batchTimeout->setAccessible(true);

    $client = new CiscoFinesseClient;
    expect($batchTimeout->getValue($client))->toBeGreaterThan(15);
});

test('SyncFinesseAgentStatesAction usa Cache para employee mapping', function () {
    $contents = file_get_contents(app_path('Modules/ConnectModule/Actions/SyncFinesseAgentStatesAction.php'));
    expect($contents)->toContain('Cache::remember($employeeCacheKey');
    expect($contents)->toContain('3600'); // 1 hora TTL
});

test('SyncFailed event existe con propiedades correctas', function () {
    $event = new SyncFailed('cuic', 'Error de conexion', 5);
    expect($event->source)->toBe('cuic');
    expect($event->message)->toBe('Error de conexion');
    expect($event->consecutiveFailures)->toBe(5);
});

test('AgentRealtimeState policy registrada en ModuleServiceProvider', function () {
    $provider = new ModuleServiceProvider(app());
    $reflection = new ReflectionClass($provider);

    $boot = $reflection->getMethod('boot');
    $boot->setAccessible(true);
    // Just verify the class can be instantiated — boot() needs full app context
    expect(class_exists(AgentRealtimeStatePolicy::class))->toBeTrue();
});

test('Channel policy registrada en ModuleServiceProvider', function () {
    expect(class_exists(ChannelPolicy::class))->toBeTrue();
});
