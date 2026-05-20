<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

use Spatie\LaravelData\Data;

class AdherenceStatusDTO extends Data
{
    public function __construct(
        public bool $isAdherent,
        public string $label,
        public string $color,
        public string $description,
    ) {}

    public static function fromStates(?array $expected, $realtime): self
    {
        if (! $expected || ! $realtime) {
            return new self(
                isAdherent: true,
                label: 'Sin Datos',
                color: 'zinc',
                description: 'Esperando sincronización de estados...'
            );
        }

        // Lógica de Adherencia simplificada:
        // - Si se espera SHIFT y el agente está productivo -> OK
        // - Si se espera INTRADAY y el agente está en el estado correcto (o no productivo si es pausa) -> OK
        $isProductive = $realtime->is_productive ?? false;
        $expectedType = $expected['type'];

        $isAdherent = match ($expectedType) {
            'SHIFT_START', 'SHIFT_END' => $isProductive,
            'LUNCH', 'BREAK' => ! $isProductive,
            'INTRADAY' => ! $isProductive, // Generalmente actividades fuera de línea
            default => true
        };

        return new self(
            isAdherent: $isAdherent,
            label: $isAdherent ? 'En Adherencia' : 'Fuera de Adherencia',
            color: $isAdherent ? 'green' : 'red',
            description: $isAdherent
                ? 'Tu estado actual coincide con lo planificado.'
                : "Se esperaba '{$expected['label']}' pero tu estado es '{$realtime->display_name}'."
        );
    }
}
