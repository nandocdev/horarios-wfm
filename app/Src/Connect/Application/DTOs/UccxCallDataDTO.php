<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\DTOs;

final readonly class UccxCallDataDTO
{
    public function __construct(
        public array $rawData,
    ) {}

    public function getCiscoCallId(): string
    {
        return $this->rawData['id_de_sesión']
            ?? $this->rawData['cisco_call_id']
            ?? $this->rawData['session_id']
            ?? '';
    }

    public function getSequenceNumber(): int
    {
        return (int) ($this->rawData['n.º_de_secuencia']
            ?? $this->rawData['sequence_number']
            ?? 0);
    }

    public function getStartedAt(): string
    {
        return $this->rawData['hora_de_inicio']
            ?? $this->rawData['started_at']
            ?? '';
    }

    public function getEndedAt(): string
    {
        return $this->rawData['hora_de_fin']
            ?? $this->rawData['ended_at']
            ?? '';
    }

    public function getContactDisposition(): int
    {
        return (int) ($this->rawData['disposición_de_contacto']
            ?? $this->rawData['contact_disposition']
            ?? 0);
    }

    public function getQueueName(): ?string
    {
        return $this->rawData['csq_name']
            ?? $this->rawData['queue_name']
            ?? null;
    }

    public function getAgentName(): ?string
    {
        return $this->rawData['nombre_del_agente']
            ?? $this->rawData['agent_name']
            ?? null;
    }

    public function getOriginatingNumber(): string
    {
        return $this->rawData['n.º_del_autor']
            ?? $this->rawData['originating_number']
            ?? '';
    }

    public function getDestinationNumber(): string
    {
        return $this->rawData['n.º_de_destino']
            ?? $this->rawData['destination_number']
            ?? $this->getCalledNumber();
    }

    public function getCalledNumber(): string
    {
        return $this->rawData['número_llamado']
            ?? $this->rawData['called_number']
            ?? '';
    }

    public function getTalkTime(): int
    {
        return (int) ($this->rawData['tiempo_de_conversación']
            ?? $this->rawData['talk_time']
            ?? 0);
    }

    public function getRingTime(): int
    {
        return (int) ($this->rawData['tiempo_de_timbre']
            ?? $this->rawData['ring_time']
            ?? 0);
    }

    public function getWorkTime(): int
    {
        return (int) ($this->rawData['tiempo_de_trabajo']
            ?? $this->rawData['work_time']
            ?? 0);
    }

    public function getQueueTime(): int
    {
        return (int) ($this->rawData['tiempo_en_cola']
            ?? $this->rawData['queue_time']
            ?? 0);
    }
}
