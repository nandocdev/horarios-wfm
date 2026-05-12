<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDependent extends Model
{
    protected $fillable = ['employee_id', 'name', 'relationship', 'birth_date'];

    protected $casts = [
        'birth_date' => 'date',
    ];
}
