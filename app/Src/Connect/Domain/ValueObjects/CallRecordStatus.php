<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\ValueObjects;

enum CallRecordStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Closed = 'closed';
}
