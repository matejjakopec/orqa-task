<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class AccountIdentifier
{
    public static function normalize(string $value): string
    {
        return Str::lower(trim($value));
    }
}
