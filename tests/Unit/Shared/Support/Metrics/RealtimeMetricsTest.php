<?php

declare(strict_types=1);

use App\Shared\Support\Metrics\RealtimeMetrics;

test('occupancy calculates productive over available time', function () {
    expect(RealtimeMetrics::occupancy(300, 60, 90, 600, 120))->toEqualWithDelta(93.75, 0.01);
    expect(RealtimeMetrics::occupancy(300, 60, 90, 0, 0))->toBe(0.0);
});

test('productivity and utilization', function () {
    expect(RealtimeMetrics::productivity(400, 600))->toEqualWithDelta(66.67, 0.01);
    expect(RealtimeMetrics::utilization(400, 500))->toBe(80.0);
    expect(RealtimeMetrics::utilization(400, 0))->toBe(100.0);
});

test('adherence rate is capped at one hundred', function () {
    expect(RealtimeMetrics::adherenceRate(420, 480))->toBe(87.5);
    expect(RealtimeMetrics::adherenceRate(500, 480))->toBe(100.0);
});

test('check adherence validates states', function () {
    expect(RealtimeMetrics::checkAdherence('TALKING', 'SHIFT'))->toBeTrue();
    expect(RealtimeMetrics::checkAdherence('NOT_READY', 'INTRADAY'))->toBeTrue();
    expect(RealtimeMetrics::checkAdherence('OFFLINE', 'SHIFT'))->toBeFalse();
});

test('conformance', function () {
    expect(RealtimeMetrics::conformance(450, 480))->toEqualWithDelta(93.75, 0.01);
});

test('login compliance checks tardiness', function () {
    $scheduled = new DateTimeImmutable('2026-07-31 08:00:00');
    $actual = new DateTimeImmutable('2026-07-31 08:06:00');

    expect(RealtimeMetrics::checkLate($scheduled, $actual, 5))->toBeTrue();
    expect(RealtimeMetrics::checkLate($scheduled, $actual, 10))->toBeFalse();
    expect(RealtimeMetrics::loginComplianceSeconds($scheduled, $actual))->toBe(360);
});

test('net staffing position', function () {
    expect(RealtimeMetrics::netStaffingPosition(8, 10))->toBe(-2.0);
});

test('service variance', function () {
    expect(RealtimeMetrics::serviceVariance(75, 80))->toBe(-5.0);
});

test('queue backlog', function () {
    expect(RealtimeMetrics::queueBacklog(15, 3, 5))->toBe(0.0);
    expect(RealtimeMetrics::queueBacklog(20, 3, 5))->toBe(5.0);
});

test('service deficit', function () {
    expect(RealtimeMetrics::serviceDeficit(10, 7))->toBe(3.0);
    expect(RealtimeMetrics::serviceDeficit(10, 12))->toBe(0.0);
});

test('availability ratio', function () {
    expect(RealtimeMetrics::availabilityRatio(7, 10))->toBe(70.0);
    expect(RealtimeMetrics::availabilityRatio(7, 0))->toBe(0.0);
});

test('occupancy ceiling', function () {
    expect(RealtimeMetrics::occupancyCeilingExceeded(90, 85))->toBeTrue();
    expect(RealtimeMetrics::occupancyCeilingExceeded(80, 85))->toBeFalse();
});
