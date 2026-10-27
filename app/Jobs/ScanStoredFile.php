<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Files\FileProcessingServiceInterface;
use App\Exceptions\StorageBusy;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ScanStoredFile implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 0;

    public int $timeout = 420;

    public int $uniqueFor = 3600;

    public function __construct(public int $fileId)
    {
        $this->timeout = (int) config('files.scanner.job_timeout_seconds', 420);
    }

    public function uniqueId(): string
    {
        return (string) $this->fileId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300, 600];
    }

    public function handle(FileProcessingServiceInterface $processing): void
    {
        try {
            $delay = $processing->scan($this->fileId);

            if ($delay !== null) {
                $this->release($delay);
            }
        } catch (StorageBusy) {
            $this->release(10);
        }
    }
}
