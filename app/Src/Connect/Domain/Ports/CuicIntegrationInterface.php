<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Ports;

interface CuicIntegrationInterface
{
    public function executeReport(string $reportType, string $dateFrom, string $dateTo, ?int $minutes = null): array;
    public function executeReportWithRetry(string $reportType, string $dateFrom, string $dateTo, ?int $minutes = null, int $maxRetries = 3): array;
    public function executeRealtimeSnapshot(string $reportType): array;
    public function executeAgentRealtimeSnapshot(array $employeeIds): array;
    public function executeAgentDetailReport(string $loginId, string $dateFrom, string $dateTo): array;
    public function executeAgentStateTransitions(string $loginId, string $dateFrom, string $dateTo): array;
    public function listReports(): array;
    public function testConnection(): bool;
}
