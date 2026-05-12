<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDisease extends Model
{
    protected $fillable = ['employee_id', 'disease_type_id', 'notes'];
}
