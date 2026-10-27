<?php

declare(strict_types=1);

namespace App\Contracts\Files;

use App\Models\StorageAccount;
use App\Models\StoredFile;

interface QuotaServiceInterface
{
    public function reserve(StorageAccount $account, int $sizeBytes): void;

    public function commit(StorageAccount $account, StoredFile $file): void;

    public function release(StorageAccount $account, StoredFile $file): void;
}
