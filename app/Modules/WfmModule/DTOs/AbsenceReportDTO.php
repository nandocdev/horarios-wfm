<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\DTOs;

use App\Modules\WfmModule\Enums\AbsenceReasonType;
use Carbon\Carbon;

readonly class AbsenceReportDTO
{
    public function __construct(
        public string $employee_number,
        public string $employee_name,
        public Carbon $absence_start_date,
        public int $absence_total_days,
        public string $employee_position,
        public string $cip_number,
        public float $base_salary,
        public float $salary_supplement,
        public bool $is_justified,
        public ?AbsenceReasonType $reason_type,
        public bool $medical_certificate_attached,
        public bool $has_witnesses,
        public ?string $observations,
        public string $department_head_name,
        public string $executive_unit,
        public ?string $discount_code,
        public ?string $discount_description,
        public ?float $discount_amount,
        public ?float $discount_balance,
        public string $accountant_name,
        public bool $discount_biweekly_authorized,
    ) {}
}
