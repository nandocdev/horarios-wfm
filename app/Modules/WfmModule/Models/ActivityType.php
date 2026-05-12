<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    protected $fillable = ['name', 'color', 'is_productive', 'is_paid'];

    protected $casts = [
        'is_productive' => 'boolean',
        'is_paid' => 'boolean',
    ];
}
