<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryFaq extends Model
{
    protected $table = 'directory_faqs';

    protected $fillable = [
        'unit_id',
        'subject',
        'procedure_steps',
        'required_documents',
        'estimated_time',
        'schedule',
        'allows_third_party',
    ];

    protected $casts = [
        'allows_third_party' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
