<?php

declare(strict_types=1);

namespace App\Data\Files;

final readonly class StoredObject
{
    public function __construct(public int $sizeBytes, public string $sha256) {}
}
