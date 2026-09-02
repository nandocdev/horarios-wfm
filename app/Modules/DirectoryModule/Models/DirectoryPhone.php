<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $service_id
 * @property string $number
 * @property string $type CISCO|DIRECTO|MOVIL|WHATSAPP_CHAT
 * @property string|null $description
 * @property Carbon|null $created_at
 */
class DirectoryPhone extends Model
{
    protected $table = 'directory_phones';

    protected $fillable = ['service_id', 'number', 'type', 'description'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(DirectoryService::class, 'service_id');
    }

    public static function sanitize(string $raw): string
    {
        return preg_replace('/[^0-9\-]/', '', trim($raw)) ?? '';
    }

    public static function inferType(string $number): string
    {
        return match (true) {
            preg_match('/^\d{5}$/', $number) === 1 => 'CISCO',
            str_starts_with($number, '513-') => 'DIRECTO',
            preg_match('/^6\d{3}-\d{4}$/', $number) === 1 => 'MOVIL',
            default => 'DIRECTO',
        };
    }
}
