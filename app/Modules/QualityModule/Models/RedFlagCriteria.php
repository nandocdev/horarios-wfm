<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Shared\Models\BaseModel;

class RedFlagCriteria extends BaseModel
{
    protected $table = 'quality_red_flag_criteria';

    protected $fillable = [
        'criterio_text',
        'perdida',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'perdida' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
