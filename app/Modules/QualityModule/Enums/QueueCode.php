<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Enums;

enum QueueCode: string
{
    case CmTr = 'CM-Tr';
    case CmCanc = 'CM-Canc';
    case CmConf = 'CM-Conf';
    case Au = 'AU';
    case Farm = 'Farm';
    case Mor = 'Mor';
    case Conf = 'CONF';
    case Sipe = 'SIPE';
    case Web = 'WEB';
    case Cigesa = 'CIGESA';
    case Fact = 'Fact';

    public function label(): string
    {
        return match ($this) {
            self::CmTr => 'Citas Médicas - Trámite',
            self::CmCanc => 'Citas Médicas - Cancelación',
            self::CmConf => 'Citas Médicas - Confirmación',
            self::Au => 'Atención al Usuario',
            self::Farm => 'Farmacia',
            self::Mor => 'Apremio y Cobro',
            self::Conf => 'Llamadas Salientes - Confirmación',
            self::Sipe => 'SIPE',
            self::Web => 'Web / Telegram / WhatsApp',
            self::Cigesa => 'CIGESA – Quejas',
            self::Fact => 'Facturación',
        };
    }

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
