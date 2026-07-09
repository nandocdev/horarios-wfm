<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalSetting extends Model
{
    protected $table = 'operational_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
        'category',
    ];

    protected $casts = [
        'value' => 'string',
    ];
}
