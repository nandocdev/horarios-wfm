<?php

declare(strict_types=1);

namespace App\Modules\HelpdeskModule\Models;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpdeskTicketComment extends Model
{
    use HasFactory;

    protected $fillable = ['ticket_id', 'author_id', 'content', 'is_internal'];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(HelpdeskTicket::class, 'ticket_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
