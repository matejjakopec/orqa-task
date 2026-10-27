<?php

declare(strict_types=1);

namespace App\Data\Files;

use App\Enums\ScanVerdict;

final readonly class ScanResult
{
    public function __construct(
        public ScanVerdict $verdict,
        public int $sizeBytes,
        public string $sha256,
        public string $engineVersion,
        public string $signatureVersion,
        public ?string $failureCode = null,
    ) {}
}
