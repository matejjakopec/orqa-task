<?php

declare(strict_types=1);

namespace App\Enums;

enum QuotaState: string
{
    case Reserved = 'reserved';
    case Used = 'used';
    case Released = 'released';
}
