<?php

declare(strict_types=1);

namespace Src\Location\Domain\Exceptions;

use InvalidArgumentException;

final class InvalidProvinceCodeException extends InvalidArgumentException
{
    public static function invalid(string $code): self
    {
        return new self("Código de provincia inválido: '{$code}'. Debe ser 2 letras mayúsculas (ej: PA, CH, BT).");
    }
}
