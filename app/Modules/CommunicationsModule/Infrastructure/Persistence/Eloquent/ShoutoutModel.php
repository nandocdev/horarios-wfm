<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ShoutoutModel extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'shoutouts';

    protected $fillable = [
        'employee_id', 'message', 'is_active', 'scheduled_at', 'archive_at',
        'status', 'approved_by', 'approved_at', 'moderation_notes',
        'version_history',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scheduled_at' => 'datetime',
        'archive_at' => 'datetime',
        'approved_at' => 'datetime',
        'version_history' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\PersonnelModule\Models\Employee::class);
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(CategoryModel::class, 'categorizable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(TagModel::class, 'taggable');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ReactionModel::class, 'shoutout_id');
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(MentionModel::class, 'mentionable');
    }
}
