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
        public string $level = 'info', // info, success, warning, danger
        public array $metadata = []
    ) {}
}
