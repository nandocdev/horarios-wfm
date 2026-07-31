<?php

declare(strict_types=1);

use App\Shared\Support\Metrics\ServiceQualityMetrics;

test('service level of offered', function () {
    expect(ServiceQualityMetrics::serviceLevel(80, 100))->toBe(80.0);
    expect(ServiceQualityMetrics::serviceLevel(80, 0))->toBe(0.0);
});

test('service level excluding short abandons', function () {
    expect(ServiceQualityMetrics::serviceLevelExcludingShortAbandons(80, 100, 5))->toEqualWithDelta(84.21, 0.01);
});

test('asa', function () {
    expect(ServiceQualityMetrics::asa(1000, 50))->toBe(20.0);
    expect(ServiceQualityMetrics::asa(1000, 0))->toBe(0.0);
});

test('abandonment rate', function () {
    expect(ServiceQualityMetrics::abandonmentRate(10, 100))->toBe(10.0);
});

test('aht includes talk hold and acw', function () {
    expect(ServiceQualityMetrics::aht(300, 60, 90, 10))->toBe(45.0);
});

test('aht components', function () {
    expect(ServiceQualityMetrics::ahtComponents(300, 60, 90, 10))->toBe([
        'talk' => 30.0,
        'hold' => 6.0,
        'acw' => 9.0,
        'aht' => 45.0,
    ]);
});

test('fcr', function () {
    expect(ServiceQualityMetrics::fcr(10, 100))->toBe(90.0);
    expect(ServiceQualityMetrics::fcr(0, 0))->toBe(0.0);
});
