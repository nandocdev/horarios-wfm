<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends BaseModel
{
    protected $table = 'quality_feedback';

    protected $fillable = [
        'evaluation_id',
        'obsfeed',
        'created_by',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
