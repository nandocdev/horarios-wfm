<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Models;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadedFile extends Model
{
    protected $table = 'uploaded_files';

    protected $fillable = [
        'agent_call_performance_id',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function agentCallPerformance(): BelongsTo
    {
        return $this->belongsTo(AgentCallPerformance::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }
}
