<?php

declare(strict_types=1);

namespace App\Contracts\Files;

use Carbon\CarbonInterface;

interface FileProcessingServiceInterface
{
    public function scan(int $fileId): ?int;

    public function delete(int $fileId): void;

    public function recover(int $fileId, CarbonInterface $staleBefore): void;
}
