<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class TimelineItemDTO extends Data
{
    public function __construct(
        public string $id,
        public string $type,
        public string $label,
        public string $startTime,
        public string $displayTime,
        public string $icon,
        public string $color,
        public ?string $endTime = null,
        public ?string $description = null,
        public bool $isReal = false,
        public bool $isPast = false,
        public bool $isCurrent = false,
        public bool $isFuture = false,
    ) {}

    public static function fromArray(array $data): self
    {
        $now = now();
        $start = Carbon::parse($data['start_time']);
        $end = isset($data['end_time']) ? Carbon::parse($data['end_time']) : null;

        $isPast = $now->gt($end ?? $start);
        $isCurrent = $now->between($start, $end ?? $start->copy()->addMinutes(30)); // Fallback if no end
        $isFuture = $now->lt($start);

        // Map status-based colors to operational ones (Past = Zinc/Gray, Current = Blue/Primary, Future = Neutral)
        $operationalColor = match (true) {
            $isPast => 'zinc',
            $isCurrent => 'primary',
            default => 'zinc', // Future is also neutral
        };

        // If it's real data, maybe we want to keep its semantic color but with lower opacity if past
        // But the user suggested: past -> grey, current -> strong blue, future -> neutral

        return new self(
            id: bin2hex(random_bytes(8)),
            type: $data['type'],
            label: $data['label'],
            startTime: $start->toIso8601String(),
            displayTime: $data['display_time'],
            icon: $data['icon'],
            color: $operationalColor,
            endTime: $end?->toIso8601String(),
            description: $data['description'] ?? null,
            isReal: $data['is_real'] ?? false,
            isPast: $isPast,
            isCurrent: $isCurrent,
            isFuture: $isFuture,
        );
    }
}
