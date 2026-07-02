<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentEmployeeDependent extends Model
{
    protected $table = 'employee_dependents';

    protected $fillable = ['employee_id', 'name', 'relationship', 'birth_date'];

    protected $casts = ['birth_date' => 'date'];
}
