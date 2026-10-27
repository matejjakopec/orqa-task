<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Files\FileProcessingServiceInterface;
use App\Contracts\Files\FileRepositoryInterface;
use App\Exceptions\StorageBusy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class RecoverPendingFiles extends Command
{
    protected $signature = 'files:recover {--stale-minutes=30 : Minimum age of an abandoned upload} {--limit=100 : Maximum records to inspect} {--resume : Continue the previous scheduled pass}';

    protected $description = 'Recover abandoned uploads, queue pending scans, and retry file cleanup once';

    public function handle(FileRepositoryInterface $files, FileProcessingServiceInterface $processing): int
    {
        $minutes = filter_var($this->option('stale-minutes'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10080]]);
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);

        if ($minutes === false || $limit === false) {
            $this->error(__('files.invalid_recovery_options'));

            return self::FAILURE;
        }

        $cutoff = now()->subMinutes($minutes);
        $completed = $busy = $failed = 0;

        $afterId = $this->option('resume') ? (int) Cache::get('recovery:files:cursor', 0) : 0;
        $candidates = $files->recoveryCandidates($cutoff, $limit, $afterId);

        foreach ($candidates as $file) {
            try {
                $processing->recover($file->id, $cutoff);
                $completed++;
            } catch (StorageBusy) {
                $busy++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        if ($this->option('resume')) {
            Cache::forever('recovery:files:cursor', $candidates->count() < $limit ? 0 : $candidates->last()->id);
        }

        $this->info(__('files.recovery_result', compact('completed', 'busy', 'failed')));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
