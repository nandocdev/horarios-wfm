<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Models;

use Illuminate\Database\Eloquent\Model;

class AgentState extends Model
{
    protected $fillable = ['external_code', 'display_name', 'is_productive', 'color_hex'];

    protected $casts = [
        'is_productive' => 'boolean',
    ];
}
