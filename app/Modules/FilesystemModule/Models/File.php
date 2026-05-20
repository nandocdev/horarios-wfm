<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Models;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class File extends Model
{
    protected $fillable = [
        'uuid', 'user_id', 'folder_id', 'name', 'original_name',
        'path', 'disk', 'size', 'mime_type', 'extension', 'is_public',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($model) => $model->uuid = (string) Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function getFormattedSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = (float) max($this->size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
