<?php

declare(strict_types=1);

use App\Shared\Support\Metrics\CapacityMetrics;

test('offered load converts calls and aht into erlangs', function () {
    expect(CapacityMetrics::offeredLoad(10, 180, 900))->toBe(2.0);
    expect(CapacityMetrics::offeredLoad(0, 180, 900))->toBe(0.0);
    expect(CapacityMetrics::offeredLoad(10, 180, 0))->toBe(0.0);
});

test('erlang c returns probability between zero and one', function () {
    expect(CapacityMetrics::erlangC(0, 1))->toBe(1.0);
    expect(CapacityMetrics::erlangC(1, 1))->toBe(1.0);
    expect(CapacityMetrics::erlangC(2, 1))->toEqualWithDelta(0.3333, 0.001);
    expect(CapacityMetrics::erlangC(3, 1))->toEqualWithDelta(0.0909, 0.001);
});

test('erlang a reduces load by abandonment rate', function () {
    expect(CapacityMetrics::erlangA(2, 1, 0.0))->toEqualWithDelta(CapacityMetrics::erlangC(2, 1), 0.0001);
    expect(CapacityMetrics::erlangA(2, 1, 1.0))->toBe(0.0);
});

test('agents for service level finds required headcount', function () {
    expect(CapacityMetrics::agentsForServiceLevel(1, 20, 300, 80))->toBe(3);
    expect(CapacityMetrics::agentsForServiceLevel(0, 20, 300, 80))->toBe(1);
});

test('required staff applies shrinkage', function () {
    expect(CapacityMetrics::requiredStaff(5, 0.2))->toBe(6.25);
    expect(CapacityMetrics::requiredStaff(5, 1.0))->toBe(0.0);
});

test('shrinkage calculates non productive percentage', function () {
    expect(CapacityMetrics::shrinkage(450, 600))->toBe(25.0);
    expect(CapacityMetrics::shrinkage(0, 0))->toBe(0.0);
});

test('interval shrinkage forecast sums planned and unplanned', function () {
    expect(CapacityMetrics::intervalShrinkageForecast(15, 8))->toBe(23.0);
});

test('interval occupancy forecast', function () {
    expect(CapacityMetrics::intervalOccupancyForecast(2, 3))->toEqualWithDelta(66.67, 0.01);
    expect(CapacityMetrics::intervalOccupancyForecast(2, 0))->toBe(0.0);
});

test('expected wait time', function () {
    expect(CapacityMetrics::expectedWaitTime(3, 1, 300))->toEqualWithDelta(13.64, 0.01);
    expect(CapacityMetrics::expectedWaitTime(1, 2, 300))->toBe(0.0);
});

test('effective staff sums capped weights', function () {
    expect(CapacityMetrics::effectiveStaff([0.8, 0.9]))->toBe(1.7);
    expect(CapacityMetrics::effectiveStaff([1.2, -0.1]))->toBe(1.0);
});
