<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Models;

use Illuminate\Database\Eloquent\Model;

class HelpdeskCategory extends Model
{
    protected $fillable = ['name', 'description', 'sla_hours', 'color', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'sla_hours' => 'integer',
    ];
}
