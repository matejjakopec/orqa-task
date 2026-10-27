<?php

declare(strict_types=1);

namespace App\Enums;

enum FileStatus: string
{
    case Receiving = 'receiving';
    case Quarantined = 'quarantined';
    case Scanning = 'scanning';
    case Available = 'available';
    case Rejected = 'rejected';
    case Deleting = 'deleting';
    case Deleted = 'deleted';
}
