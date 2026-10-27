<?php

declare(strict_types=1);

namespace App\Contracts\Files;

use App\Data\Files\ReconciliationReport;

interface StorageRepairServiceInterface
{
    public function reconcile(int $limit, bool $repair, int $afterUserId, int $afterFileId, string $afterArtifact): ReconciliationReport;
}
