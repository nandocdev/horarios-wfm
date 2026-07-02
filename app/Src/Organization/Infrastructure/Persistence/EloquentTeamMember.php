<?php

declare(strict_types=1);

namespace App\Src\Organization\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentTeamMember extends Model
{
    protected $table = 'team_members';

    protected $fillable = ['team_id', 'employee_id', 'joined_at', 'left_at', 'is_active'];

    protected $casts = [
        'joined_at' => 'date',
        'left_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(EloquentTeam::class);
    }
}
