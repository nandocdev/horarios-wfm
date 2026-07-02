<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Infrastructure\Persistence;

use App\Src\HumanResources\Application\Mappers\EmployeeRecordMapper;
use App\Src\HumanResources\Domain\Entities\EmployeeDisease;
use App\Src\HumanResources\Domain\Entities\EmployeeRecord;
use App\Src\HumanResources\Domain\Repositories\EmployeeRecordRepositoryInterface;

final class EloquentEmployeeRecordRepository implements EmployeeRecordRepositoryInterface
{
    public function findByEmployeeNumber(string $employeeNumber): ?EmployeeRecord
    {
        $employee = \App\Modules\PersonnelModule\Models\Employee::with([
            'diseases', 'disabilities', 'dependents',
        ])->where('employee_number', $employeeNumber)->first();

        if (! $employee) return null;

        return $this->buildRecord($employee);
    }

    public function findById(int $id): ?EmployeeRecord
    {
        $employee = \App\Modules\PersonnelModule\Models\Employee::with([
            'diseases', 'disabilities', 'dependents',
        ])->find($id);

        if (! $employee) return null;

        return $this->buildRecord($employee);
    }

    public function saveDisease(EmployeeDisease $disease): EmployeeDisease
    {
        $data = EmployeeRecordMapper::diseaseToEloquent($disease);

        if (config('human-resources.encrypt_medical_notes', false)) {
            $data['notes'] = encrypt($data['notes']);
        }

        $eloquent = EloquentEmployeeDisease::updateOrCreate(
            ['id' => $disease->id()],
            $data,
        );

        return EmployeeRecordMapper::diseaseToDomain($eloquent);
    }

    public function findDiseasesByEmployee(int $employeeId): array
    {
        return EloquentEmployeeDisease::where('employee_id', $employeeId)->get()
            ->map(fn (EloquentEmployeeDisease $e) => EmployeeRecordMapper::diseaseToDomain($e))
            ->toArray();
    }

    public function findDisabilitiesByEmployee(int $employeeId): array
    {
        return EloquentEmployeeDisability::where('employee_id', $employeeId)->get()
            ->map(fn (EloquentEmployeeDisability $e) => EmployeeRecordMapper::disabilityToDomain($e))
            ->toArray();
    }

    public function findDependentsByEmployee(int $employeeId): array
    {
        return EloquentEmployeeDependent::where('employee_id', $employeeId)->get()
            ->map(fn (EloquentEmployeeDependent $e) => EmployeeRecordMapper::dependentToDomain($e))
            ->toArray();
    }

    private function buildRecord($employee): EmployeeRecord
    {
        $diseases = $employee->diseases
            ->map(fn ($d) => EmployeeRecordMapper::diseaseToDomain(
                new EloquentEmployeeDisease($d->toArray())
            ))
            ->toArray();

        $disabilities = $employee->disabilities
            ->map(fn ($d) => EmployeeRecordMapper::disabilityToDomain(
                new EloquentEmployeeDisability($d->toArray())
            ))
            ->toArray();

        $dependents = $employee->dependents
            ->map(fn ($d) => EmployeeRecordMapper::dependentToDomain(
                new EloquentEmployeeDependent($d->toArray())
            ))
            ->toArray();

        return new EmployeeRecord(
            id: $employee->id,
            employeeNumber: $employee->employee_number,
            firstName: $employee->first_name,
            lastName: $employee->last_name,
            email: $employee->email,
            birthDate: $employee->birth_date ? new \DateTimeImmutable($employee->birth_date->format('Y-m-d')) : null,
            gender: $employee->gender,
            bloodType: $employee->blood_type,
            phone: $employee->phone,
            mobilePhone: $employee->mobile_phone,
            hireDate: $employee->hire_date ? new \DateTimeImmutable($employee->hire_date->format('Y-m-d')) : null,
            isActive: (bool) $employee->is_active,
            diseases: $diseases,
            disabilities: $disabilities,
            dependents: $dependents,
        );
    }
}
