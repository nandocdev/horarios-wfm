<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceReasonCode extends Model
{
    protected $fillable = ['name', 'short_code', 'requires_attachment', 'is_excused', 'color'];

    protected $casts = [
        'requires_attachment' => 'boolean',
        'is_excused' => 'boolean',
    ];
}
