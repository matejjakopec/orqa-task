<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Files\StorageAccountRepositoryInterface;
use App\Models\StorageAccount;
use Illuminate\Database\Eloquent\Collection;

final class EloquentStorageAccountRepository implements StorageAccountRepositoryInterface
{
    public function lockForUser(int $userId): StorageAccount
    {
        return StorageAccount::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
    }

    public function findForUser(int $userId): ?StorageAccount
    {
        return StorageAccount::query()->find($userId);
    }

    /** @return Collection<int, StorageAccount> */
    public function reconciliationCandidates(int $afterUserId, int $limit): Collection
    {
        return StorageAccount::query()->where('user_id', '>', $afterUserId)->orderBy('user_id')->limit($limit)->get();
    }

    public function setCharges(StorageAccount $account, int $usedBytes, int $reservedBytes): void
    {
        $account->used_bytes = $usedBytes;
        $account->reserved_bytes = $reservedBytes;
        $account->save();
    }
}
