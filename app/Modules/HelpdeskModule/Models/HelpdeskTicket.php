<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Models;

use App\Modules\HelpdeskModule\Enums\TicketPriority;
use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    public function getSlaDeadlineAttribute(): ?CarbonInterface
    {
        if (! $this->relationLoaded('category') && ! $this->category) {
            return null;
        }

        return $this->created_at->addHours($this->category->sla_hours);
    }

    public function getSlaStatusAttribute(): string
    {
        $deadline = $this->sla_deadline;
        if (! $deadline) {
            return 'unknown';
        }

        $closedValues = array_map(fn (TicketStatus $s) => $s->value, TicketStatus::closed());
        $isClosed = in_array($this->status, $closedValues, true);

        if ($isClosed) {
            $closedAt = $this->resolved_at ?? $this->closed_at;

            return $closedAt && $closedAt->lte($deadline) ? 'compliant' : 'breached';
        }

        $now = now();

        if ($now->greaterThan($deadline)) {
            return 'breached';
        }

        $totalHours = (int) $this->category->sla_hours;
        $elapsedHours = $this->created_at->diffInHours($now);
        $elapsedPct = $totalHours > 0 ? ($elapsedHours / $totalHours) * 100 : 0;

        return $elapsedPct >= 75 ? 'at_risk' : 'on_track';
    }

    /**
     * @return string[]
     */
    private static function closedStatusValues(): array
    {
        return array_map(fn (TicketStatus $s) => $s->value, TicketStatus::closed());
    }

    /**
     * Scope para filtrar por estado de SLA.
     */
    public function scopeWhereSla(Builder $query, string $status): Builder
    {
        $driver = DB::getDriverName();
        $closedValues = self::closedStatusValues();

        $deadlineExpr = match ($driver) {
            'pgsql' => DB::raw("helpdesk_tickets.created_at + (helpdesk_categories.sla_hours * INTERVAL '1 hour')"),
            default => DB::raw('DATE_ADD(helpdesk_tickets.created_at, INTERVAL helpdesk_categories.sla_hours HOUR)'),
        };

        return $query
            ->join('helpdesk_categories', 'helpdesk_tickets.category_id', '=', 'helpdesk_categories.id')
            ->where(function (Builder $q) use ($status, $deadlineExpr, $closedValues) {
                match ($status) {
                    'breached' => $q
                        ->whereNotIn('helpdesk_tickets.status', $closedValues)
                        ->where($deadlineExpr, '<', DB::raw('NOW()')),
                    'at_risk' => $q
                        ->whereNotIn('helpdesk_tickets.status', $closedValues)
                        ->where($deadlineExpr, '>=', DB::raw('NOW()'))
                        ->where($deadlineExpr, '<=', DB::raw("NOW() + (helpdesk_categories.sla_hours * INTERVAL '1 hour') * 0.25")),
                    'on_track' => $q
                        ->whereNotIn('helpdesk_tickets.status', $closedValues)
                        ->where($deadlineExpr, '>', DB::raw("NOW() + (helpdesk_categories.sla_hours * INTERVAL '1 hour') * 0.25")),
                    'compliant' => $q
                        ->whereIn('helpdesk_tickets.status', $closedValues)
                        ->whereColumn(DB::raw('COALESCE(helpdesk_tickets.resolved_at, helpdesk_tickets.closed_at)'), '<=', $deadlineExpr),
                    default => null,
                };
            });
    }
}
