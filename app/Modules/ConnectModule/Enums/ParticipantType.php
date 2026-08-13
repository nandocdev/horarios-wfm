<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Enums;

/**
 * Tipo de participante (autor o destino) en una llamada en Cisco Unified CCX.
 *
 * Fuente: Documentación oficial Cisco Unified CCX / CCDR.
 */
enum ParticipantType: int
{
    case Agent = 1;
    case Device = 2;
    case Unknown = 3;

    public function label(): string
    {
        return match ($this) {
            self::Agent => 'Agente',
            self::Device => 'Dispositivo / CTI',
            self::Unknown => 'Desconocido / Externo',
        };
    }
}
