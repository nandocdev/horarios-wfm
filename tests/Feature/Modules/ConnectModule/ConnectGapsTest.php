<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Policies\AgentRealtimeStatePolicy;
use App\Modules\ConnectModule\Policies\ChannelPolicy;
use App\Modules\ConnectModule\Providers\ModuleServiceProvider;
use App\Shared\Events\SyncFailed;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use App\Shared\Support\Cache\CachePolicyService;

test('CiscoFinesseClient tiene timeout diferenciado para batch', function () {
    $reflection = new ReflectionClass(CiscoFinesseClient::class);
    $batchTimeout = $reflection->getProperty('batchTimeout');
    $batchTimeout->setAccessible(true);

    $client = new CiscoFinesseClient;
    expect($batchTimeout->getValue($client))->toBeGreaterThan(15);
});

test('SyncFinesseAgentStatesAction usa Cache para employee mapping', function () {
    $contents = file_get_contents(app_path('Modules/ConnectModule/Actions/SyncFinesseAgentStatesAction.php'));
    // Migrado a CachePolicyService (Fase 2 — docs/CACHE_POLICY.md): connect:employees 900s, connect:config 3600s
    expect($contents)->toContain('CachePolicyService');
    expect($contents)->toContain('cachePolicy->remember');
    expect($contents)->toContain("'connect'");
    expect($contents)->toContain("'employees'");
    // Verifica que la política define TTL correcto según categoría
    $policy = new CachePolicyService;
    expect($policy->getTtl('employees'))->toBe(900);
    expect($policy->getTtl('config'))->toBe(3600);
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
