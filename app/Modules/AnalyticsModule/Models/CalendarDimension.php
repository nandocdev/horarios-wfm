<?php

declare(strict_types=1);

namespace App\Modules\AnalyticsModule\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarDimension extends Model
{
    protected $table = 'analytics_calendar_dimension';

    protected $primaryKey = 'date';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'date',
        'day',
        'month',
        'year',
        'quarter',
        'day_of_week',
        'day_name',
        'month_name',
        'week_of_year',
        'is_weekend',
        'is_business_day',
        'is_holiday',
        'holiday_name',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_weekend' => 'boolean',
            'is_business_day' => 'boolean',
            'is_holiday' => 'boolean',
        ];
    }
}
