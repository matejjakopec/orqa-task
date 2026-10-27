<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Users\UserRepositoryInterface;
use App\Jobs\SendAccountSetupLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class RecoverAccountSetupLinks extends Command
{
    protected $signature = 'users:recover-setup-links {--limit=100 : Maximum pending accounts to inspect} {--resume : Continue the previous scheduled pass}';

    protected $description = 'Queue unsent setup links for pending accounts older than five minutes';

    public function handle(UserRepositoryInterface $users): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);

        if ($limit === false) {
            $this->error('Use a limit of 1–1000 records.');

            return self::FAILURE;
        }

        $afterId = $this->option('resume') ? (int) Cache::get('recovery:setup-links:cursor', 0) : 0;
        $pending = $users->pendingSetupLinks(now()->subMinutes(5), $limit, $afterId);
        $queued = $failed = 0;

        foreach ($pending as $user) {
            try {
                SendAccountSetupLink::dispatch($user->id);
                $queued++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        if ($this->option('resume')) {
            Cache::forever('recovery:setup-links:cursor', $pending->count() < $limit ? 0 : $pending->last()->id);
        }

        $this->info("Setup links considered for queueing: {$queued}. Failed: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
