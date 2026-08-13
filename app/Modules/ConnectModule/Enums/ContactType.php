<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Enums;

/**
 * Tipos de contacto de una llamada en Cisco Unified CCX.
 *
 * Fuente: Documentación oficial Cisco Unified CCX / CCDR.
 */
enum ContactType: int
{
    case Incoming = 1;
    case Outgoing = 2;
    case Internal = 3;
    case Redirect = 4;
    case TransferIn = 5;
    case OutboundPreview = 6;
    case OutboundIvr = 7;
    case OutboundAgent = 8;

    public function label(): string
    {
        return match ($this) {
            self::Incoming => 'Entrante',
            self::Outgoing => 'Saliente',
            self::Internal => 'Interna',
            self::Redirect => 'Redirección',
            self::TransferIn => 'Transferencia entrante',
            self::OutboundPreview => 'Vista preliminar saliente',
            self::OutboundIvr => 'IVR saliente',
            self::OutboundAgent => 'Agente saliente',
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [self::Incoming, self::Redirect, self::TransferIn], true);
    }

    public function isOutbound(): bool
    {
        return in_array($this, [self::Outgoing, self::OutboundPreview, self::OutboundIvr, self::OutboundAgent], true);
    }

    public function isInternal(): bool
    {
        return $this === self::Internal;
    }
}
