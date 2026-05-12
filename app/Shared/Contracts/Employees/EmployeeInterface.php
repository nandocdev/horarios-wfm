<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Employees;

interface EmployeeInterface
{
    public function getId(): int|string;

    public function getFullName(): string;

    public function getEmployeeNumber(): string;
}
