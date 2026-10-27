<?php

declare(strict_types=1);

namespace App\Data\Files;

final readonly class StorageArtifact
{
    public function __construct(
        public string $relativePath,
        public string $storageKey,
        public int $modifiedAt,
        public int $sizeBytes,
        public bool $temporary,
    ) {}
}
