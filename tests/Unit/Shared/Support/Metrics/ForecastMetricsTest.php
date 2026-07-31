<?php

declare(strict_types=1);

use App\Shared\Support\Metrics\ForecastMetrics;

test('volume forecast factor composes components', function () {
    expect(ForecastMetrics::volumeForecastFactor(100, 1.1, 0.95, 1.02))->toBe(106.59);
});

test('mape calculates absolute percentage error', function () {
    expect(ForecastMetrics::mape(100, 110))->toBe(10.0);
    expect(ForecastMetrics::mape(100, 90))->toBe(10.0);
    expect(ForecastMetrics::mape(0, 110))->toBe(0.0);
});

test('bias returns signed percentage error', function () {
    expect(ForecastMetrics::bias(100, 110))->toBe(10.0);
    expect(ForecastMetrics::bias(100, 90))->toBe(-10.0);
});

test('wape handles empty and zero actuals gracefully', function () {
    expect(ForecastMetrics::wape([], []))->toBe(0.0);
    expect(ForecastMetrics::wape([0, 0], [0, 0]))->toBe(0.0);
});

test('wape calculates weighted absolute percentage error', function () {
    expect(ForecastMetrics::wape([100, 200], [110, 190]))->toEqualWithDelta(6.67, 0.01);
});

test('rmse penalizes large errors', function () {
    expect(ForecastMetrics::rmse([0, 0, 0]))->toBe(0.0);
    expect(ForecastMetrics::rmse([10, -10]))->toBe(10.0);
});

test('rmse from series calculates errors', function () {
    expect(ForecastMetrics::rmseFromSeries([100, 200], [110, 190]))->toBe(10.0);
});

test('vmr and overdispersion factor', function () {
    expect(ForecastMetrics::vmr(100, 50))->toBe(2.0);
    expect(ForecastMetrics::overdispersionFactor(2.0))->toBe(2.0);
    expect(ForecastMetrics::overdispersionFactor(0.8))->toBe(1.0);
});

test('intraday reforecast scales by trend', function () {
    expect(ForecastMetrics::intradayReforecast(100, 110, 100))->toBe(110.0);
    expect(ForecastMetrics::intradayReforecast(100, 110, 0))->toBe(100.0);
});

test('channel mix returns percentage', function () {
    expect(ForecastMetrics::channelMix(30, 100))->toBe(30.0);
    expect(ForecastMetrics::channelMix(30, 0))->toBe(0.0);
});
