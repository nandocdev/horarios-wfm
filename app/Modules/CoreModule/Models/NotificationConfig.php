<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Models;

use App\Shared\Models\BaseModel;

class NotificationConfig extends BaseModel
{
    protected $fillable = [
        'event_type',
        'label',
        'description',
        'is_enabled',
        'channels',
        'recipient_type',
        'recipient_config',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'channels' => 'array',
            'recipient_config' => 'array',
        ];
    }
}
