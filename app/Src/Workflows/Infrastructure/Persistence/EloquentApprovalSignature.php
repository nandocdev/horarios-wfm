<?php

declare(strict_types=1);

namespace App\Src\Workflows\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentApprovalSignature extends Model
{
    protected $table = 'approval_signatures';

    protected $fillable = ['approval_request_id', 'approver_id', 'action', 'comment', 'level'];

    public $timestamps = false;
}
