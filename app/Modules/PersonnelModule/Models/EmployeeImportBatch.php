<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeImportBatch extends Model
{
    use HasUlids;

    protected $table = 'employee_import_batches';

    protected $fillable = [
        'batch_id',
        'original_filename',
        'stored_path',
        'status',
        'chunk_size',
        'total_rows',
        'processed_rows',
        'imported_rows',
        'rejected_rows',
        'errors',
        'created_by',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'chunk_size' => 'integer',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'imported_rows' => 'integer',
            'rejected_rows' => 'integer',
            'errors' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
