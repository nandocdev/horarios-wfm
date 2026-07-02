<?php

declare(strict_types=1);

namespace App\Src\Wfm\Infrastructure\Persistence;

use App\Src\Wfm\Application\Mappers\IntradayMapper;
use App\Src\Wfm\Domain\Entities\ActivityType;
use App\Src\Wfm\Domain\Entities\ApprovedIntradayPeriod;
use App\Src\Wfm\Domain\Entities\IntradayActivity;
use App\Src\Wfm\Domain\Repositories\IntradayRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentIntradayRepository implements IntradayRepositoryInterface
{
    public function saveActivity(IntradayActivity $activity): IntradayActivity
    {
        $start = $activity->startTime()->format('Y-m-d H:i:s');
        $end = $activity->endTime()->format('Y-m-d H:i:s');
        $tstzRange = "[{$start}, {$end})";

        $eloquent = EloquentIntradayActivity::updateOrCreate(
            ['id' => $activity->id()],
            [
                'employee_id' => $activity->employeeId(),
                'activity_type_id' => $activity->activityTypeId(),
                'approved_period_id' => $activity->approvedPeriodId(),
                'time_range' => $tstzRange,
                'notes' => $activity->notes(),
            ],
        );

        return IntradayMapper::activityToDomain($eloquent);
    }

    public function findActivitiesByEmployee(int $employeeId, DateTimeImmutable $date): array
    {
        $dayStart = $date->format('Y-m-d 00:00:00');
        $dayEnd = $date->format('Y-m-d 23:59:59');

        if (DB::getDriverName() === 'pgsql') {
            $results = EloquentIntradayActivity::where('employee_id', $employeeId)
                ->whereRaw('time_range && tstzrange(?, ?)', [$dayStart, $dayEnd])
                ->get();
        } else {
            $results = EloquentIntradayActivity::where('employee_id', $employeeId)->get()
                ->filter(function ($a) use ($dayStart, $dayEnd) {
                    $range = $a->time_range;
                    return $range && str_contains($range, substr($dayStart, 0, 10));
                });
        }

        return $results->map(fn ($e) => IntradayMapper::activityToDomain($e))->toArray();
    }

    public function findOverlapping(int $employeeId, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        if (DB::getDriverName() === 'pgsql') {
            $results = EloquentIntradayActivity::where('employee_id', $employeeId)
                ->whereRaw('time_range && tstzrange(?, ?)', [$startStr, $endStr])
                ->get();
        } else {
            $results = collect();
        }

        return $results->map(fn ($e) => IntradayMapper::activityToDomain($e))->toArray();
    }

    public function saveActivityType(ActivityType $type): ActivityType
    {
        $eloquent = EloquentActivityType::updateOrCreate(
            ['id' => $type->id()],
            [
                'name' => $type->name(),
                'color' => $type->color(),
                'is_productive' => $type->isProductive(),
                'is_paid' => $type->isPaid(),
            ],
        );

        return IntradayMapper::activityTypeToDomain($eloquent);
    }

    public function findAllActivityTypes(): array
    {
        return EloquentActivityType::all()
            ->map(fn ($e) => IntradayMapper::activityTypeToDomain($e))
            ->toArray();
    }

    public function saveApprovedPeriod(ApprovedIntradayPeriod $period): ApprovedIntradayPeriod
    {
        $eloquent = EloquentApprovedIntradayPeriod::updateOrCreate(
            ['id' => $period->id()],
            [
                'team_id' => $period->teamId(),
                'activity_definition_id' => $period->activityDefinitionId(),
                'date' => $period->date()->format('Y-m-d'),
                'start_time' => $period->startTime(),
                'end_time' => $period->endTime(),
                'max_slots' => $period->maxSlots(),
                'notes' => $period->notes(),
            ],
        );

        return IntradayMapper::approvedPeriodToDomain($eloquent);
    }

    public function findApprovedPeriodById(int $id): ?ApprovedIntradayPeriod
    {
        $eloquent = EloquentApprovedIntradayPeriod::find($id);
        return $eloquent ? IntradayMapper::approvedPeriodToDomain($eloquent) : null;
    }

    public function findApprovedPeriodsByTeam(int $teamId, DateTimeImmutable $date): array
    {
        return EloquentApprovedIntradayPeriod::where('team_id', $teamId)
            ->whereDate('date', $date->format('Y-m-d'))
            ->get()
            ->map(fn ($e) => IntradayMapper::approvedPeriodToDomain($e))
            ->toArray();
    }
}
