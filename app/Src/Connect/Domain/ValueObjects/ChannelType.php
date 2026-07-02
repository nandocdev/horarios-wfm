<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\ValueObjects;

enum ChannelType: string
{
    case Voice = 'voice';
    case Chat = 'chat';
    case Email = 'email';
    case Social = 'social';
}
