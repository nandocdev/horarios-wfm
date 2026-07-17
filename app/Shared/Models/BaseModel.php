<?php

declare(strict_types=1);

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasUlids;

    /**
     * Indica si los IDs son autoincrementales.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * El tipo de ID de la clave primaria.
     *
     * @var string
     */
    protected $keyType = 'string';
}
