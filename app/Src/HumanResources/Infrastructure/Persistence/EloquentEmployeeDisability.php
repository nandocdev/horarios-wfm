<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentEmployeeDisability extends Model
{
    protected $table = 'employee_disabilities';

    protected $fillable = ['employee_id', 'disability_type_id', 'notes'];
}
