<?php

declare(strict_types=1);

namespace App\Src\Organization\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentTeam extends Model
{
    protected $table = 'teams';

    protected $fillable = ['name', 'description', 'supervisor_id', 'is_active', 'base_schedule_id', 'cisco_team_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function members(): HasMany
    {
        return $this->hasMany(EloquentTeamMember::class, 'team_id');
    }
}
