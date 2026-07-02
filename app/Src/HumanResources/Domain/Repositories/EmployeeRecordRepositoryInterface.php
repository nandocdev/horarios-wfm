<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Domain\Repositories;

use App\Src\HumanResources\Domain\Entities\EmployeeDisease;
use App\Src\HumanResources\Domain\Entities\EmployeeRecord;

interface EmployeeRecordRepositoryInterface
{
    public function findByEmployeeNumber(string $employeeNumber): ?EmployeeRecord;
    public function findById(int $id): ?EmployeeRecord;
    public function saveDisease(EmployeeDisease $disease): EmployeeDisease;
    public function findDiseasesByEmployee(int $employeeId): array;
    public function findDisabilitiesByEmployee(int $employeeId): array;
    public function findDependentsByEmployee(int $employeeId): array;
}
