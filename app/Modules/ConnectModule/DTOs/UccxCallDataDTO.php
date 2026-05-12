<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\DTOs;

use Carbon\CarbonImmutable;

readonly class UccxCallDataDTO
{
    public function __construct(
        public string $ciscoCallId,
        public int $sequenceNumber,
        public CarbonImmutable $startedAt,
        public CarbonImmutable $endedAt,
        public int $contactDisposition,
        public ?string $queueName,
        public ?string $agentName,
        public string $originatingNumber,
        public string $destinationNumber,
        public string $calledNumber,
        public int $talkTime,
        public int $ringTime,
        public int $workTime,
        public int $queueTime,
    ) {}

    public static function fromCsvRow(array $row): self
    {
        // Formato esperado: Wed Apr 15 00:15:27 EST 2026
        $startedAt = CarbonImmutable::parse($row['hora_de_inicio']);
        $endedAt = CarbonImmutable::parse($row['hora_de_fin']);

        return new self(
            ciscoCallId: $row['id_de_sesión'],
            sequenceNumber: (int) ($row['n.º_de_secuencia'] ?? 0),
            startedAt: $startedAt,
            endedAt: $endedAt,
            contactDisposition: (int) $row['disposición_de_contacto'],
            queueName: $row['csq_name'] ?: null,
            agentName: $row['nombre_del_agente'] ?: null,
            originatingNumber: $row['n.º_del_autor'],
            destinationNumber: $row['n.º_de_destino'] ?: $row['número_llamado'],
            calledNumber: $row['número_llamado'],
            talkTime: (int) ($row['tiempo_de_conversación'] ?: 0),
            ringTime: (int) ($row['tiempo_de_timbre'] ?: 0),
            workTime: (int) ($row['tiempo_de_trabajo'] ?: 0),
            queueTime: (int) ($row['tiempo_en_cola'] ?: 0),
        );
    }
}
