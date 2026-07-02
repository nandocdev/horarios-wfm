<?php

declare(strict_types=1);

namespace App\Src\Analytics\Domain\Entities;

use DateTimeImmutable;

final class AgentDailyMetric {
    public function __construct(
        private ?int $id,
        private readonly int $employeeId,
        private readonly DateTimeImmutable $metricDate,
        private int $loginSeconds = 0,
        private int $productiveSeconds = 0,
        private int $callsTotal = 0,
        private int $talkSeconds = 0,
        private float $weightedAht = 0.0,
        private float $capacityCalls = 0.0,
        private float $capacityGap = 0.0,
        private float $workUnits = 0.0,
        private float $availabilityPct = 0.0,
        private float $efficiencyPct = 0.0,
        private float $pwiPct = 0.0,
        private array $queueDistribution = [],
        private ?float $adherencePct = null,
        private ?float $productivityPct = null,
        private ?float $utilizationPct = null,
        private ?float $occupancyPct = null,
        private ?int $scheduledSeconds = null,
        private ?int $adherentSeconds = null,
    ) {
    }

    public static function create(int $employeeId, DateTimeImmutable $metricDate): self {
        return new self(null, $employeeId, $metricDate);
    }

    public function id(): ?int {
        return $this->id;
    }
    public function employeeId(): int {
        return $this->employeeId;
    }
    public function metricDate(): DateTimeImmutable {
        return $this->metricDate;
    }
    public function loginSeconds(): int {
        return $this->loginSeconds;
    }
    public function productiveSeconds(): int {
        return $this->productiveSeconds;
    }
    public function callsTotal(): int {
        return $this->callsTotal;
    }
    public function talkSeconds(): int {
        return $this->talkSeconds;
    }
    public function weightedAht(): float {
        return $this->weightedAht;
    }
    public function capacityCalls(): float {
        return $this->capacityCalls;
    }
    public function capacityGap(): float {
        return $this->capacityGap;
    }
    public function workUnits(): float {
        return $this->workUnits;
    }
    public function availabilityPct(): float {
        return $this->availabilityPct;
    }
    public function efficiencyPct(): float {
        return $this->efficiencyPct;
    }
    public function pwiPct(): float {
        return $this->pwiPct;
    }
    public function queueDistribution(): array {
        return $this->queueDistribution;
    }
    public function adherencePct(): ?float {
        return $this->adherencePct;
    }
    public function productivityPct(): ?float {
        return $this->productivityPct;
    }
    public function utilizationPct(): ?float {
        return $this->utilizationPct;
    }

    public function updateMetrics(array $data): void {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
