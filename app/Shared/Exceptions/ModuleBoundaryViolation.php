<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use LogicException;

/**
 * Violación de frontera modular.
 *
 * Se lanza cuando un módulo intenta consumir código privado de otro
 * (app/Modules/{Other}/Internal/*) o infringe las reglas de acceso
 * definidas en docs/tmp/Modules.md §3.
 */
final class ModuleBoundaryViolation extends LogicException
{
    public static function internalAccess(string $consumerModule, string $targetModule, string $targetClass): self
    {
        return new self(
            sprintf(
                'Module boundary violation: "%s" cannot access "%s" (belongs to "%s\\Internal"). Consume only Actions/DTOs/Events/Models/Contracts.',
                $consumerModule,
                $targetClass,
                $targetModule
            )
        );
    }

    public static function generic(string $message): self
    {
        return new self($message);
    }
}
