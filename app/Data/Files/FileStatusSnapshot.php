<?php

declare(strict_types=1);

namespace App\Data\Files;

final readonly class FileStatusSnapshot
{
    /** @param list<array{public_id: string, status: string, label: string, variant: string, reason: ?string, pending: bool, removed: bool, download_url: ?string, delete_url: ?string}> $files */
    public function __construct(public array $files, public ?StorageSummary $storage) {}

    /** @return array{files: array, storage: ?array} */
    public function toArray(): array
    {
        return ['files' => $this->files, 'storage' => $this->storage?->toArray()];
    }
}
