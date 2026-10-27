<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Files\FileRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class FileProcessingStatus extends Command
{
    protected $signature = 'files:status {--json : Output aggregate metrics as JSON}';

    protected $description = 'Report processing and cleanup backlog without exposing file or account details';

    public function handle(FileRepositoryInterface $files): int
    {
        $threshold = max(1, (int) config('operations.pending_file_minutes'));
        $metrics = [];
        $hasBacklog = false;

        foreach ($files->processingSummary() as $summary) {
            $age = max(0, (int) floor(CarbonImmutable::parse($summary['oldest_pending_at'])->diffInMinutes(now())));
            $alert = $age >= $threshold;
            $hasBacklog = $hasBacklog || $alert;
            $metrics[] = ['status' => $summary['status'], 'count' => $summary['count'], 'oldest_minutes' => $age, 'alert' => $alert];
        }

        if ($hasBacklog) {
            Log::warning('File processing backlog exceeds its age threshold.', ['threshold_minutes' => $threshold, 'states' => $metrics]);
        }

        if ($this->option('json')) {
            $this->line(json_encode(['checked_at' => now()->toIso8601String(), 'threshold_minutes' => $threshold, 'alert' => $hasBacklog, 'states' => $metrics], JSON_THROW_ON_ERROR));
        } else {
            $this->table(['State', 'Count', 'Oldest (minutes)', 'Status'], array_map(fn (array $row): array => [$row['status'], $row['count'], $row['oldest_minutes'], $row['alert'] ? 'ALERT' : 'OK'], $metrics));
            $this->info($hasBacklog ? 'Delayed work found. Inspect scanner, worker, and recovery logs.' : 'No overdue file processing or cleanup found.');
        }

        return $hasBacklog ? self::FAILURE : self::SUCCESS;
    }
}
