<?php

declare(strict_types=1);

namespace App\Src\Analytics\Domain\Services;

final class KpiCalculationService
{
    public function productivity(float $productiveMinutes, float $connectedMinutes): float
    {
        if ($connectedMinutes <= 0) return 0.0;
        return round(($productiveMinutes / $connectedMinutes) * 100, 1);
    }

    public function utilization(float $productiveMinutes, float $baseMinutes): float
    {
        if ($baseMinutes <= 0) return $productiveMinutes > 0 ? 100.0 : 0.0;
        return round(min(($productiveMinutes / $baseMinutes) * 100, 100.0), 1);
    }

    public function adherence(string $realState, string $expectedType): bool
    {
        $real = strtoupper($realState);
        $offline = in_array($real, ['OFFLINE', 'LOGOUT', 'LOGGED_OUT', 'UNKNOWN'], true);
        $productive = in_array($real, ['READY', 'TALKING', 'WORK', 'RESERVED', 'HOLD', 'OUTBOUND'], true);

        if ($offline) return $expectedType === 'OFF';
        if ($expectedType === 'OFF') return false;
        if ($expectedType === 'SHIFT') return $productive;
        if (in_array($expectedType, ['INTRADAY', 'EXCEPTION'], true)) return $real === 'NOT_READY';

        return false;
    }

    public function adherencePercentage(int $adherentSeconds, int $scheduledSeconds): float
    {
        if ($scheduledSeconds <= 0) return 100.0;
        return round(($adherentSeconds / $scheduledSeconds) * 100, 1);
    }

    public function aht(float $totalTalkSeconds, float $totalWorkSeconds, int $totalCalls): float
    {
        if ($totalCalls <= 0) return 0.0;
        return round(($totalTalkSeconds + $totalWorkSeconds) / $totalCalls);
    }

    public function occupancy(float $talkTime, float $holdTime, float $workTime, float $loggedTime, float $auxTime): float
    {
        $denominator = $loggedTime - $auxTime;
        if ($denominator <= 0) return 0.0;
        return round((($talkTime + $holdTime + $workTime) / $denominator) * 100, 1);
    }

    public function serviceLevel(int $callsWithinThreshold, int $totalOffered): float
    {
        if ($totalOffered <= 0) return 0.0;
        return round(($callsWithinThreshold / $totalOffered) * 100, 1);
    }

    public function coverageRate(int $availableAgents, int $scheduledAgents): float
    {
        if ($scheduledAgents <= 0) return 0.0;
        return round(($availableAgents / $scheduledAgents) * 100, 1);
    }

    public function absenteeismRate(float $absentMinutes, float $scheduledProductiveMinutes): float
    {
        if ($scheduledProductiveMinutes <= 0) return 0.0;
        return round(($absentMinutes / $scheduledProductiveMinutes) * 100, 1);
    }

    public function pwi(float $availabilityPct, float $efficiencyPct): float
    {
        return round(($availabilityPct / 100) * ($efficiencyPct / 100) * 100, 2);
    }

    public function capacityCalls(float $productiveSeconds, float $weightedAht): float
    {
        if ($weightedAht <= 0) return 0.0;
        return round($productiveSeconds / $weightedAht, 2);
    }

    public function formatDuration(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
