<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallQueue extends Model
{
    protected $table = 'call_queues';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'channel_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function subtypes(): HasMany
    {
        return $this->hasMany(CaseSubtype::class, 'queue_id', 'id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function activeNames(): array
    {
        return self::active()->orderBy('name')->pluck('name')->toArray();
    }
}
