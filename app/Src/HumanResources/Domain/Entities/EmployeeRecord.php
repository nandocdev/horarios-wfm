<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Domain\Entities;

use App\Src\HumanResources\Domain\ValueObjects\MedicalNotes;
use DateTimeImmutable;

final class EmployeeRecord
{
    private ?int $id;
    private string $employeeNumber;
    private string $firstName;
    private string $lastName;
    private string $email;
    private ?DateTimeImmutable $birthDate;
    private ?string $gender;
    private ?string $bloodType;
    private ?string $phone;
    private ?string $mobilePhone;
    private ?DateTimeImmutable $hireDate;
    private ?DateTimeImmutable $terminationDate;
    private bool $isActive;
    private array $diseases;
    private array $disabilities;
    private array $dependents;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        string $employeeNumber,
        string $firstName,
        string $lastName,
        string $email,
        ?DateTimeImmutable $birthDate = null,
        ?string $gender = null,
        ?string $bloodType = null,
        ?string $phone = null,
        ?string $mobilePhone = null,
        ?DateTimeImmutable $hireDate = null,
        ?DateTimeImmutable $terminationDate = null,
        bool $isActive = true,
        array $diseases = [],
        array $disabilities = [],
        array $dependents = [],
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->employeeNumber = $employeeNumber;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->birthDate = $birthDate;
        $this->gender = $gender;
        $this->bloodType = $bloodType;
        $this->phone = $phone;
        $this->mobilePhone = $mobilePhone;
        $this->hireDate = $hireDate;
        $this->terminationDate = $terminationDate;
        $this->isActive = $isActive;
        $this->diseases = $diseases;
        $this->disabilities = $disabilities;
        $this->dependents = $dependents;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public function id(): ?int { return $this->id; }
    public function employeeNumber(): string { return $this->employeeNumber; }
    public function firstName(): string { return $this->firstName; }
    public function lastName(): string { return $this->lastName; }
    public function fullName(): string { return trim("{$this->firstName} {$this->lastName}"); }
    public function email(): string { return $this->email; }
    public function birthDate(): ?DateTimeImmutable { return $this->birthDate; }
    public function gender(): ?string { return $this->gender; }
    public function bloodType(): ?string { return $this->bloodType; }
    public function phone(): ?string { return $this->phone; }
    public function mobilePhone(): ?string { return $this->mobilePhone; }
    public function hireDate(): ?DateTimeImmutable { return $this->hireDate; }
    public function terminationDate(): ?DateTimeImmutable { return $this->terminationDate; }
    public function isActive(): bool { return $this->isActive; }
    public function diseases(): array { return $this->diseases; }
    public function disabilities(): array { return $this->disabilities; }
    public function dependents(): array { return $this->dependents; }

    public function addDisease(EmployeeDisease $disease): void
    {
        $this->diseases[] = $disease;
    }

    public function addDisability(EmployeeDisability $disability): void
    {
        $this->disabilities[] = $disability;
    }

    public function addDependent(EmployeeDependent $dependent): void
    {
        $this->dependents[] = $dependent;
    }

    public function terminate(?DateTimeImmutable $date = null): void
    {
        $this->isActive = false;
        $this->terminationDate = $date ?? new DateTimeImmutable();
    }

    public function reactivate(): void
    {
        $this->isActive = true;
        $this->terminationDate = null;
    }
}
