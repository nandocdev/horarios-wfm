<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Notifications;

use App\Shared\DTOs\NotificationDTO;
use App\Shared\Notifications\BaseNotification;

class MaintenanceModeNotification extends BaseNotification
{
    public function __construct(
        NotificationDTO $dto,
    ) {
        parent::__construct($dto);
    }
}
