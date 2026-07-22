<?php

declare(strict_types=1);

namespace App\Modules\ReportingModule\Enums;

enum ReportFormatEnum: string
{
    case Pdf = 'pdf';
    case Xls = 'xls';
}
