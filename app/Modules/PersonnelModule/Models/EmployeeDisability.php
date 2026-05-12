<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDisability extends Model
{
    protected $fillable = ['employee_id', 'disability_type_id', 'notes'];
}
