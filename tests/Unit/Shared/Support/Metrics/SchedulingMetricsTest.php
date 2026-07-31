<?php

declare(strict_types=1);

use App\Shared\Support\Metrics\SchedulingMetrics;

test('coverage uses min of scheduled and required', function () {
    expect(SchedulingMetrics::coverage(8, 10))->toBe(80.0);
    expect(SchedulingMetrics::coverage(12, 10))->toBe(100.0);
    expect(SchedulingMetrics::coverage(8, 0))->toBe(0.0);
});

test('schedule efficiency delta is symmetric', function () {
    expect(SchedulingMetrics::scheduleEfficiencyDelta(8, 10))->toBe(80.0);
    expect(SchedulingMetrics::scheduleEfficiencyDelta(12, 10))->toBe(80.0);
});

test('staffing efficiency returns required over scheduled', function () {
    expect(SchedulingMetrics::staffingEfficiency(8, 10))->toBe(0.8);
    expect(SchedulingMetrics::staffingEfficiency(10, 8))->toBe(1.25);
});

test('schedule fit score sums absolute differences', function () {
    expect(SchedulingMetrics::scheduleFitScore([10, 12], [9, 13]))->toBe(2.0);
});

test('schedule fit score can be normalized', function () {
    expect(SchedulingMetrics::scheduleFitScore([10, 12], [9, 13], true))->toEqualWithDelta(0.0909, 0.0001);
});

test('schedule fit score returns zero for mismatched arrays', function () {
    expect(SchedulingMetrics::scheduleFitScore([10], [9, 13]))->toBe(0.0);
});

test('schedule compliance score returns checklist percentage', function () {
    expect(SchedulingMetrics::scheduleComplianceScore(true, true, true, true))->toBe(100.0);
    expect(SchedulingMetrics::scheduleComplianceScore(true, false, true, false))->toBe(50.0);
});
