<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Models;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileShare extends Model
{
    protected $fillable = [
        'file_id', 'folder_id', 'user_id', 'shared_by_id', 'access_level', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }
}
