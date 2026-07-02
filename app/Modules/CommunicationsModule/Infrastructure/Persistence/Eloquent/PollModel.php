<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class PollModel extends Model
{
    protected $table = 'polls';

    protected $fillable = [
        'question', 'options', 'is_active', 'expires_at', 'scheduled_at',
        'archive_at', 'reminder_sent_at', 'status', 'approved_by',
        'approved_at', 'moderation_notes', 'version_history',
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'archive_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'version_history' => 'array',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(PollResponseModel::class, 'poll_id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(CategoryModel::class, 'categorizable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(TagModel::class, 'taggable');
    }
}
