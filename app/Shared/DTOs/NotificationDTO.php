<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

use Spatie\LaravelData\Data;

class NotificationDTO extends Data
{
    public function __construct(
        public string $title,
        public string $message,
        public string $actionUrl = '#',
        public string $icon = 'information-circle',
        public string $level = 'info',
        public array $metadata = [],
        public ?string $notificationType = null,
        public ?string $summary = null,
        public array $facts = [],
        public ?string $recommendation = null,
        public array $actions = [],
        public ?string $resourceType = null,
        public ?string $resourceId = null,
    ) {}
}
