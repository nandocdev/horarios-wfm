<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Application\Mappers;

use App\Src\HumanResources\Domain\Entities\EmployeeDependent;
use App\Src\HumanResources\Domain\Entities\EmployeeDisease;
use App\Src\HumanResources\Domain\Entities\EmployeeDisability;
use App\Src\HumanResources\Domain\Entities\EmployeeRecord;
use App\Src\HumanResources\Domain\ValueObjects\MedicalNotes;
use App\Src\HumanResources\Infrastructure\Persistence\EloquentEmployeeDisease;
use App\Src\HumanResources\Infrastructure\Persistence\EloquentEmployeeDisability;
use App\Src\HumanResources\Infrastructure\Persistence\EloquentEmployeeDependent;
use DateTimeImmutable;

final class EmployeeRecordMapper
{
    public static function diseaseToDomain(EloquentEmployeeDisease $e): EmployeeDisease
    {
        return new EmployeeDisease(
            id: $e->id,
            employeeId: $e->employee_id,
            diseaseTypeId: $e->disease_type_id,
            notes: new MedicalNotes($e->notes ?? ''),
        );
    }

    public static function diseaseToEloquent(EmployeeDisease $d): array
    {
        return [
            'employee_id' => $d->employeeId(),
            'disease_type_id' => $d->diseaseTypeId(),
            'notes' => $d->notes()->value(),
        ];
    }

    public static function disabilityToDomain(EloquentEmployeeDisability $e): EmployeeDisability
    {
        return new EmployeeDisability(
            id: $e->id,
            employeeId: $e->employee_id,
            disabilityTypeId: $e->disability_type_id,
            notes: $e->notes,
        );
    }

    public static function disabilityToEloquent(EmployeeDisability $d): array
    {
        return [
            'employee_id' => $d->employeeId(),
            'disability_type_id' => $d->disabilityTypeId(),
            'notes' => $d->notes(),
        ];
    }

    public static function dependentToDomain(EloquentEmployeeDependent $e): EmployeeDependent
    {
        return new EmployeeDependent(
            id: $e->id,
            employeeId: $e->employee_id,
            name: $e->name,
            relationship: $e->relationship,
            birthDate: $e->birth_date ? self::toImmutable($e->birth_date) : null,
        );
    }

    public static function dependentToEloquent(EmployeeDependent $d): array
    {
        return [
            'employee_id' => $d->employeeId(),
            'name' => $d->name(),
            'relationship' => $d->relationship(),
            'birth_date' => $d->birthDate()?->format('Y-m-d'),
        ];
    }

    private static function toImmutable(mixed $date): DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        if ($date instanceof \DateTime) return DateTimeImmutable::createFromMutable($date);
        return new DateTimeImmutable((string) $date);
    }
}
