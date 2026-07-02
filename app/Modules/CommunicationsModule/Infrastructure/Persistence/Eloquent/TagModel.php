<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent;

use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class TagModel extends Model
{
    use Auditable;

    protected $table = 'tags';

    protected $fillable = [
        'name', 'slug', 'color', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function news(): MorphToMany
    {
        return $this->morphedByMany(NewsModel::class, 'taggable');
    }
}
