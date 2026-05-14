<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Notifications;

use App\Shared\DTOs\NotificationDTO;
use App\Shared\Notifications\BaseNotification;

class ShiftSwapApprovedNotification extends BaseNotification
{
    // Hereda todo de BaseNotification, solo se especializa el DTO en el constructor si fuera necesario
    // o se sobreescriben métodos para lógica específica.
}
