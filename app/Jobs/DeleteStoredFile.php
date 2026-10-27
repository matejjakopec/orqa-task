<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Files\FileProcessingServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class DeleteStoredFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public function __construct(public int $fileId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(FileProcessingServiceInterface $processing): void
    {
        $processing->delete($this->fileId);
    }
}
