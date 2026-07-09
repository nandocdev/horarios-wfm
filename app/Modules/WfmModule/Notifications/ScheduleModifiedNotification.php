<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Notifications;

use App\Shared\Notifications\BaseNotification;

/**
 * Notificación cuando un horario ya publicado es modificado por un supervisor o WFM.
 */
class ScheduleModifiedNotification extends BaseNotification {}
