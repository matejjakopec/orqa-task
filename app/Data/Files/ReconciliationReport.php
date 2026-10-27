<?php

declare(strict_types=1);

namespace App\Data\Files;

final readonly class ReconciliationReport
{
    /** @param list<array{kind: string, reference: string, result: string}> $issues */
    public function __construct(
        public array $issues,
        public int $accountsChecked,
        public int $filesChecked,
        public int $artifactsChecked,
        public int $failed,
        public int $nextUserId,
        public int $nextFileId,
        public string $nextArtifact,
    ) {}
}
