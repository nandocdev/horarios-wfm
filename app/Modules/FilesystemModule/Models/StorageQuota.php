<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Models;

use Illuminate\Database\Eloquent\Model;

class StorageQuota extends Model
{
    protected $fillable = [
        'target_type',
        'target_id',
        'quota_limit',
    ];

    /**
     * Convierte MB a Bytes para almacenamiento.
     */
    public static function mbToBytes(int $mb): int
    {
        return $mb * 1024 * 1024;
    }

    /**
     * Convierte Bytes a MB para visualización.
     */
    public static function bytesToMb(int $bytes): int
    {
        return (int) round($bytes / 1024 / 1024);
    }
}
