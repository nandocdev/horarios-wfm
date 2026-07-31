<?php

declare(strict_types=1);

use App\Shared\Support\Metrics\CostWorkforceMetrics;

test('cost per contact', function () {
    expect(CostWorkforceMetrics::costPerContact(5000, 100))->toBe(50.0);
    expect(CostWorkforceMetrics::costPerContact(5000, 0))->toBe(0.0);
});

test('cost per resolution', function () {
    expect(CostWorkforceMetrics::costPerResolution(5000, 80))->toBe(62.5);
});

test('attrition rate', function () {
    expect(CostWorkforceMetrics::attritionRate(5, 50))->toBe(10.0);
    expect(CostWorkforceMetrics::attritionRate(5, 0))->toBe(0.0);
});
