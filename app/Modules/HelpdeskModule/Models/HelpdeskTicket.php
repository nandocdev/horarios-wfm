<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Models;

use App\Modules\HelpdeskModule\Enums\TicketPriority;
use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpdeskTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject', 'description', 'category_id', 'creator_id',
        'assigned_agent_id', 'status', 'priority', 'resolved_at', 'closed_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpdeskCategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'creator_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_agent_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(HelpdeskTicketComment::class, 'ticket_id')->orderBy('created_at', 'asc');
    }

    public function getStatusLabelAttribute(): string
    {
        $status = TicketStatus::tryFrom($this->status);

        return $status?->label() ?? ucfirst($this->status);
    }

    public function getPriorityLabelAttribute(): string
    {
        $priority = TicketPriority::tryFrom($this->priority);

        return $priority?->label() ?? ucfirst($this->priority);
    }
}
