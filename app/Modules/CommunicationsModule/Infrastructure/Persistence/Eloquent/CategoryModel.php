<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent;

use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class CategoryModel extends Model
{
    use Auditable;

    protected $table = 'categories';

    protected $fillable = [
        'name', 'slug', 'description', 'color', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function news(): MorphToMany
    {
        return $this->morphedByMany(NewsModel::class, 'categorizable');
    }
}
