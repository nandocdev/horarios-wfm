<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryServiceRule extends Model
{
    protected $table = 'directory_service_rules';

    protected $fillable = [
        'unit_id',
        'service_name',
        'allows_first_visit',
        'requires_referral',
        'referral_specialists',
        'requirements',
        'exclusions',
        'external_referral',
        'operational_notes',
    ];

    protected $casts = [
        'allows_first_visit' => 'boolean',
        'requires_referral' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
