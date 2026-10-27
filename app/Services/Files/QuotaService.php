<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Files\QuotaServiceInterface;
use App\Enums\QuotaState;
use App\Exceptions\QuotaExceeded;
use App\Models\StorageAccount;
use App\Models\StoredFile;
use LogicException;

final class QuotaService implements QuotaServiceInterface
{
    public function reserve(StorageAccount $account, int $sizeBytes): void
    {
        if ($sizeBytes < 0) {
            throw new LogicException('A storage charge cannot be negative.');
        }

        if ($sizeBytes > $account->quota_bytes - $account->used_bytes - $account->reserved_bytes) {
            throw new QuotaExceeded;
        }

        $account->reserved_bytes += $sizeBytes;
        $account->save();
    }

    public function commit(StorageAccount $account, StoredFile $file): void
    {
        $this->assertOwner($account, $file);

        if ($file->quota_state === QuotaState::Used) {
            return;
        }

        if ($file->quota_state !== QuotaState::Reserved || $account->reserved_bytes < $file->size_bytes) {
            throw new LogicException('Only an existing reservation can become used storage.');
        }

        $account->reserved_bytes -= $file->size_bytes;
        $account->used_bytes += $file->size_bytes;
        $account->save();
        $file->quota_state = QuotaState::Used;
    }

    public function release(StorageAccount $account, StoredFile $file): void
    {
        $this->assertOwner($account, $file);

        if ($file->quota_state === QuotaState::Released) {
            return;
        }

        $counter = $file->quota_state === QuotaState::Used ? 'used_bytes' : 'reserved_bytes';

        if ($account->{$counter} < $file->size_bytes) {
            throw new LogicException('The recorded storage charge is inconsistent.');
        }

        $account->{$counter} -= $file->size_bytes;
        $account->save();
        $file->quota_state = QuotaState::Released;
    }

    private function assertOwner(StorageAccount $account, StoredFile $file): void
    {
        if ($account->user_id !== $file->owner_id) {
            throw new LogicException('The storage charge belongs to a different account.');
        }
    }
}
