<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    protected $fillable = [
        'code', 'name', 'color', 'requires_justification',
        'affects_availability', 'is_active',
    ];

    protected $casts = [
        'requires_justification' => 'boolean',
        'affects_availability' => 'boolean',
        'is_active' => 'boolean',
    ];
}
