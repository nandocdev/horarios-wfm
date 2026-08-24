<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Schedules;

use App\Shared\DTOs\Schedules\ScheduleDayDTO;
use Carbon\CarbonInterface;

interface ScheduleServiceInterface
{
    public function getScheduleForEmployee(int $employeeId, CarbonInterface $date): ScheduleDayDTO;

    public function getBatchSchedules(array $employeeIds, CarbonInterface $date): array;

    /**
     * Retorna las fechas (más recientes primero) en las que el empleado tenía
     * programación activa y no estaba exento por una excepción de día completo.
     *
     * Implementación batched: no consulta por día, sino por rango de fechas.
     *
     * @return string[] Formato Y-m-d, orden ascendente
     */
    public function recentWorkedDates(int $employeeId, int $count, CarbonInterface $through): array;
}
