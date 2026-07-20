<?php

declare(strict_types=1);

use App\Modules\PersonnelModule\Models\EmploymentStatus;
use App\Modules\PersonnelModule\Policies\EmploymentStatusPolicy;
use Illuminate\Support\Facades\Gate;

test('employment status tiene policy registrada', function () {
    $policy = Gate::getPolicyFor(EmploymentStatus::class);
    expect($policy)->toBeInstanceOf(EmploymentStatusPolicy::class);
});
