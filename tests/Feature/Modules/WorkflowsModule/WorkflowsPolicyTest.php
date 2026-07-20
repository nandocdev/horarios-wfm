<?php

declare(strict_types=1);

use App\Modules\WorkflowsModule\Policies\WorkflowRequestPolicy;
use App\Modules\WorkflowsModule\Providers\ModuleServiceProvider;

test('WorkflowRequest policy registrada en ModuleServiceProvider', function () {
    $provider = new ModuleServiceProvider(app());
    // Verify no exception is thrown on boot
    expect(class_exists(WorkflowRequestPolicy::class))->toBeTrue();
});

test('WorkflowsModule registrado en config/modules.php', function () {
    $config = require config_path('modules.php');
    expect(in_array(
        ModuleServiceProvider::class,
        $config['enabled']
    ))->toBeTrue();
});
