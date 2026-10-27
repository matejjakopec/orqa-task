<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Log;

final class LogQueueBacklog
{
    public function handle(QueueBusy $event): void
    {
        Log::warning('Queue backlog exceeds its size threshold.', [
            'connection' => $event->connectionName,
            'queue' => $event->queue,
            'size' => $event->size,
        ]);
    }
}
