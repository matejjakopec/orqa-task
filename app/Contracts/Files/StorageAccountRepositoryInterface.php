<?php

declare(strict_types=1);

namespace App\Contracts\Files;

use App\Models\StorageAccount;
use Illuminate\Database\Eloquent\Collection;

interface StorageAccountRepositoryInterface
{
    public function lockForUser(int $userId): StorageAccount;

    public function findForUser(int $userId): ?StorageAccount;

    /** @return Collection<int, StorageAccount> */
    public function reconciliationCandidates(int $afterUserId, int $limit): Collection;

    public function setCharges(StorageAccount $account, int $usedBytes, int $reservedBytes): void;
}
