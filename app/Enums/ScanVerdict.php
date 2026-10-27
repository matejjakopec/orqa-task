<?php

declare(strict_types=1);

namespace App\Enums;

enum ScanVerdict: string
{
    case Clean = 'clean';
    case Rejected = 'rejected';
    case Unavailable = 'unavailable';
}
