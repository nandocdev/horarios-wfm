<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Identity;

interface UserInterface
{
    public function getId(): int|string;

    public function getEmail(): string;

    public function getName(): string;
}
