<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Operations;

use App\Modules\OperationsModule\Services\PerformanceService;
use Tests\TestCase;

class PerformanceServiceTest extends TestCase
{
    private PerformanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PerformanceService::class);
    }

    public function test_format_delta_positive(): void
    {
        $reflection = new \ReflectionClass(PerformanceService::class);
        $method = $reflection->getMethod('formatDelta');
        $method->setAccessible(true);

        $this->assertEquals('+5.5%', $method->invoke($this->service, 10.5, 5.0));
    }

    public function test_format_delta_negative(): void
    {
        $reflection = new \ReflectionClass(PerformanceService::class);
        $method = $reflection->getMethod('formatDelta');
        $method->setAccessible(true);

        $this->assertEquals('-2.3%', $method->invoke($this->service, 5.0, 7.3));
    }

    public function test_format_delta_zero(): void
    {
        $reflection = new \ReflectionClass(PerformanceService::class);
        $method = $reflection->getMethod('formatDelta');
        $method->setAccessible(true);

        $this->assertEquals('0.0%', $method->invoke($this->service, 5.0, 5.0));
    }
}
