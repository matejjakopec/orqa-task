<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class ByteFormatter
{
    public static function format(int $bytes): string
    {
        if ($bytes < 0) {
            throw new InvalidArgumentException('Byte counts must be non-negative.');
        }

        foreach (['TiB' => 1024 ** 4, 'GiB' => 1024 ** 3, 'MiB' => 1024 ** 2, 'KiB' => 1024] as $unit => $divisor) {
            if ($bytes >= $divisor) {
                return number_format($bytes / $divisor, 2, '.', ',').' '.$unit;
            }
        }

        return number_format($bytes).' B';
    }
}
