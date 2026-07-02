<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentEmployeeDisease extends Model
{
    protected $table = 'employee_diseases';

    protected $fillable = ['employee_id', 'disease_type_id', 'notes'];
}
