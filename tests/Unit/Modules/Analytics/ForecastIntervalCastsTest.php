<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Analytics;

use App\Modules\AnalyticsModule\Models\ForecastInterval;
use Tests\TestCase;

class ForecastIntervalCastsTest extends TestCase
{
    public function test_numeric_forecast_attributes_are_cast(): void
    {
        $interval = new ForecastInterval([
            'interval_minutes' => '15',
            'call_volume_forecast' => 120,
            'talk_time_seconds_forecast' => '3000',
            'aht_seconds_forecast' => '180.50',
            'staff_required' => '12.75',
        ]);

        $this->assertIsInt($interval->interval_minutes);
        $this->assertIsInt($interval->call_volume_forecast);
        $this->assertIsInt($interval->talk_time_seconds_forecast);
        $this->assertIsFloat($interval->aht_seconds_forecast);
        $this->assertSame(180.5, $interval->aht_seconds_forecast);
        $this->assertIsFloat($interval->staff_required);
    }
}
