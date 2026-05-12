<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Observers;

use App\Modules\PersonnelModule\Models\EmploymentStatus;
use Illuminate\Support\Facades\DB;

class EmploymentStatusObserver
{
    public function updated(EmploymentStatus $status): void
    {
        if ($status->wasChanged('is_active')) {
            $old = $status->getOriginal('is_active');
            $new = $status->is_active;

            if ($old === true && $new === false) {
                DB::transaction(function () use ($status) {
                    $status->employees()
                        ->where('is_active', true)
                        ->update(['is_active' => false]);
                });
            }

            // No activamos empleados automáticamente, esto tiene riesgo de negocio.
        }
    }
}
