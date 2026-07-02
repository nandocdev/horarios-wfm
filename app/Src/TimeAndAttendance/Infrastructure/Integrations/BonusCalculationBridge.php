<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Infrastructure\Integrations;

use App\Src\TimeAndAttendance\Domain\Entities\IncidentType;
use App\Src\TimeAndAttendance\Infrastructure\Persistence\EloquentAttendanceIncident;
use DateTimeImmutable;

final class BonusCalculationBridge
{
    public function getUnjustifiedIncidentsCount(int $employeeId, string $startDate, string $endDate): int
    {
        return EloquentAttendanceIncident::where('employee_id', $employeeId)
            ->whereIn('status', ['open', 'unjustified'])
            ->whereBetween('incident_date', [$startDate, $endDate])
            ->count();
    }

    public function getLateMinutes(int $employeeId, string $startDate, string $endDate): int
    {
        return EloquentAttendanceIncident::where('employee_id', $employeeId)
            ->where('status', 'open')
            ->whereBetween('incident_date', [$startDate, $endDate])
            ->count() * 5;
    }

    public function hasPerfectAttendance(int $employeeId, string $startDate, string $endDate): bool
    {
        return EloquentAttendanceIncident::where('employee_id', $employeeId)
            ->whereBetween('incident_date', [$startDate, $endDate])
            ->count() === 0;
    }

    public function getMonthlySummary(\DateTimeInterface $date): array
    {
        $monthStart = (new DateTimeImmutable($date->format('Y-m-01')))->format('Y-m-d');
        $monthEnd = (new DateTimeImmutable($date->format('Y-m-t')))->format('Y-m-d');

        $incidents = EloquentAttendanceIncident::selectRaw('employee_id, status, COUNT(*) as total')
            ->whereBetween('incident_date', [$monthStart, $monthEnd])
            ->groupBy('employee_id', 'status')
            ->get();

        return $incidents->toArray();
    }
}
