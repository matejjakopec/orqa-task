<?php

declare(strict_types=1);

namespace App\Data\Files;

final readonly class FileDownload
{
    public function __construct(public FileReadStream $stream, public string $name, public int $sizeBytes) {}
}
