<?php

declare(strict_types=1);

namespace App\Src\TimeAndAttendance\Application\Handlers;

use App\Src\TimeAndAttendance\Domain\Repositories\AttendanceRepositoryInterface;
use DateTimeImmutable;

final class JustifyIncidentHandler
{
    public function __construct(
        private AttendanceRepositoryInterface $repository,
    ) {}

    public function handle(int $incidentId, string $comment, ?int $userId = null): void
    {
        $incident = $this->repository->findIncidentById($incidentId);

        if ($incident === null) {
            throw new \RuntimeException("Incident #{$incidentId} not found.");
        }

        if (! $incident->isOpen()) {
            throw new \DomainException('La incidencia ya fue procesada.');
        }

        $incident->justify($comment);

        $this->repository->saveIncident($incident);
    }

    public function resolve(int $incidentId, int $userId, string $comment): void
    {
        $incident = $this->repository->findIncidentById($incidentId);

        if ($incident === null) {
            throw new \RuntimeException("Incident #{$incidentId} not found.");
        }

        $incident->resolve($userId, $comment);

        $this->repository->saveIncident($incident);
    }
}
