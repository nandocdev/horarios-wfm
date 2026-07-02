<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Application\Handlers;

use App\Src\HumanResources\Application\DTOs\RegisterDiseaseDTO;
use App\Src\HumanResources\Domain\Entities\EmployeeDisease;
use App\Src\HumanResources\Domain\Events\EmployeeDiseaseRegistered;
use App\Src\HumanResources\Domain\Repositories\EmployeeRecordRepositoryInterface;
use App\Src\HumanResources\Domain\ValueObjects\MedicalNotes;

final class RegisterEmployeeDiseaseHandler
{
    public function __construct(
        private EmployeeRecordRepositoryInterface $repository,
    ) {}

    public function handle(RegisterDiseaseDTO $dto): EmployeeDisease
    {
        $notes = new MedicalNotes($dto->notes);

        $disease = EmployeeDisease::register(
            employeeId: $dto->employeeId,
            diseaseTypeId: $dto->diseaseTypeId,
            notes: $notes,
        );

        $saved = $this->repository->saveDisease($disease);

        event(new EmployeeDiseaseRegistered($saved, $dto->registeredByUserId));

        return $saved;
    }
}
