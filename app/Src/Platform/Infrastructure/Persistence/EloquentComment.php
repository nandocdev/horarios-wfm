<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentComment extends Model {
    protected $table = 'comments';

    protected $fillable = [
        'news_id',
        'user_id',
        'content',
        'parent_id',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function news(): BelongsTo {
        return $this->belongsTo(EloquentNews::class, 'news_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo('App\Modules\CoreModule\Models\User', 'user_id');
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany {
        return $this->hasMany(self::class, 'parent_id');
    }
}
