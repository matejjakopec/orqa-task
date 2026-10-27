<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Files\StorageRepairServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class ReconcileStorage extends Command
{
    protected $signature = 'storage:reconcile
        {--repair : Repair confirmed discrepancies and remove aged unreferenced artifacts}
        {--limit=100 : Maximum accounts, files, and artifacts per pass}
        {--after-user=0 : Start after this internal account user ID}
        {--after-file=0 : Start after this internal file ID}
        {--after-artifact= : Start after this generated artifact path}
        {--resume : Continue the previous pass using cached cursors}';

    protected $description = 'Report storage discrepancies; changes require the explicit --repair option';

    public function handle(StorageRepairServiceInterface $storage): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        $afterUser = filter_var($this->option('after-user'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $afterFile = filter_var($this->option('after-file'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $afterArtifact = (string) $this->option('after-artifact');

        if ($limit === false || $afterUser === false || $afterFile === false
            || ($afterArtifact !== '' && preg_match('/\A(?:objects\/[a-f0-9-]{36}|temporary\/[a-f0-9-]{36}\.part)\z/', $afterArtifact) !== 1)) {
            $this->error('Use a limit of 1–1000, non-negative IDs, and a generated artifact cursor.');

            return self::FAILURE;
        }

        $repair = (bool) $this->option('repair');
        $cursorKey = 'reconciliation:'.($repair ? 'repair' : 'report').':cursor';

        if ($this->option('resume')) {
            if ($afterUser !== 0 || $afterFile !== 0 || $afterArtifact !== '') {
                $this->error('Use either --resume or explicit cursors.');

                return self::FAILURE;
            }

            $cursor = Cache::get($cursorKey, ['user' => 0, 'file' => 0, 'artifact' => '']);
            $afterUser = (int) $cursor['user'];
            $afterFile = (int) $cursor['file'];
            $afterArtifact = (string) $cursor['artifact'];
        }

        $this->info($repair ? 'Repair mode: confirmed discrepancies may be changed.' : 'Report only: objects, file states, and storage counters will not be changed.');

        try {
            $report = $storage->reconcile($limit, $repair, $afterUser, $afterFile, $afterArtifact);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Reconciliation could not finish. Inspect server logs; storage unavailability is never treated as absence.');

            return self::FAILURE;
        }

        if ($report->issues !== []) {
            $this->table(['Kind', 'Reference', 'Finding'], $report->issues);
        }

        $this->info("Checked accounts: {$report->accountsChecked}; files: {$report->filesChecked}; aged artifacts: {$report->artifactsChecked}; failed: {$report->failed}.");
        $this->line('Next cursors: --after-user='.$report->nextUserId.' --after-file='.$report->nextFileId.' --after-artifact='.$report->nextArtifact);

        if ($this->option('resume')) {
            Cache::forever($cursorKey, ['user' => $report->nextUserId, 'file' => $report->nextFileId, 'artifact' => $report->nextArtifact]);
        }

        return $report->failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
