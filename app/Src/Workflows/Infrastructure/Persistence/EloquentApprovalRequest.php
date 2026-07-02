<?php

declare(strict_types=1);

namespace App\Src\Workflows\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $fillable = [
        'type', 'requester_id', 'payload', 'state',
        'reason', 'rejection_reason', 'required_levels',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function signatures(): HasMany
    {
        return $this->hasMany(EloquentApprovalSignature::class, 'approval_request_id');
    }
}
