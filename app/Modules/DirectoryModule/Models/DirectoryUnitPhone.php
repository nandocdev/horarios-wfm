<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryUnitPhone extends Model
{
    protected $table = 'directory_unit_phones';

    protected $fillable = ['unit_id', 'number', 'type', 'description'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
