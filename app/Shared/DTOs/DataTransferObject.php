<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

use Spatie\LaravelData\Data;

/**
 * Base inmutable para DTOs del monolito modular.
 *
 * Extiende Spatie Laravel Data para validación/transformación.
 * Proporciona factory tipado y serialización consistente.
 *
 * ADR Modules.md §2: todos los DTOs transversales o de contrato
 * entre módulos deben extender esta clase.
 */
abstract class DataTransferObject extends Data {}
