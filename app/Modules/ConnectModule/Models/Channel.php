<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Channel extends Model {
    protected $table = 'channels';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function queues() {
        return $this->hasMany(CallQueue::class, 'channel_id', 'id');
    }

    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    protected static function booted(): void {
        static::creating(function (Channel $channel) {
            if (empty($channel->id)) {
                $channel->id = (string) Str::ulid();
            }
        });
    }
}
