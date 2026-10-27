<?php

declare(strict_types=1);

namespace App\Data\Files;

use App\Models\StorageAccount;
use App\Support\ByteFormatter;

final readonly class StorageSummary
{
    public function __construct(public int $quotaBytes, public int $usedBytes, public int $reservedBytes) {}

    public static function fromAccount(StorageAccount $account): self
    {
        return new self($account->quota_bytes, $account->used_bytes, $account->reserved_bytes);
    }

    /** @return array{quota_bytes: int, used_bytes: int, reserved_bytes: int, charged_bytes: int, labels: array<string, string>} */
    public function toArray(): array
    {
        return [
            'quota_bytes' => $this->quotaBytes, 'used_bytes' => $this->usedBytes,
            'reserved_bytes' => $this->reservedBytes, 'charged_bytes' => $this->usedBytes + $this->reservedBytes,
            'labels' => [
                'quota' => ByteFormatter::format($this->quotaBytes),
                'used' => ByteFormatter::format($this->usedBytes),
                'reserved' => ByteFormatter::format($this->reservedBytes),
                'free' => ByteFormatter::format($this->quotaBytes - $this->usedBytes - $this->reservedBytes),
            ],
        ];
    }
}
