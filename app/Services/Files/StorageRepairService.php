<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Audit\AuditWriterInterface;
use App\Contracts\Files\FileRepositoryInterface;
use App\Contracts\Files\FileStorageInterface;
use App\Contracts\Files\StorageAccountRepositoryInterface;
use App\Contracts\Files\StorageRepairServiceInterface;
use App\Data\Files\ReconciliationReport;
use App\Data\Files\StorageArtifact;
use App\Enums\FileStatus;
use App\Enums\QuotaState;
use App\Exceptions\StorageBusy;
use App\Jobs\DeleteStoredFile;
use App\Jobs\ScanStoredFile;
use App\Models\StorageAccount;
use App\Models\StoredFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class StorageRepairService implements StorageRepairServiceInterface
{
    public function __construct(
        private FileRepositoryInterface $files,
        private StorageAccountRepositoryInterface $accounts,
        private FileStorageInterface $storage,
        private AuditWriterInterface $audit,
    ) {}

    public function reconcile(int $limit, bool $repair, int $afterUserId, int $afterFileId, string $afterArtifact): ReconciliationReport
    {
        $accounts = $this->accounts->reconciliationCandidates($afterUserId, $limit);
        $files = $this->files->reconciliationCandidates($afterFileId, $limit);
        $issues = [];
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                $result = $this->reconcileAccount($account, $repair);

                if ($result !== null) {
                    $issues[] = ['kind' => 'account', 'reference' => (string) $account->user_id, 'result' => $result];
                }
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
                $issues[] = ['kind' => 'account', 'reference' => (string) $account->user_id, 'result' => 'Inspection failed; no discrepancy is assumed.'];
            }
        }

        foreach ($files as $file) {
            try {
                $result = $this->reconcileFile($file, $repair);

                if ($result !== null) {
                    $issues[] = ['kind' => 'file', 'reference' => $file->public_id, 'result' => $result];
                }
            } catch (StorageBusy) {
                $issues[] = ['kind' => 'file', 'reference' => $file->public_id, 'result' => 'Busy; deferred to a later pass.'];
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
                $issues[] = ['kind' => 'file', 'reference' => $file->public_id, 'result' => 'Inspection or repair failed; see server logs.'];
            }
        }

        $cutoff = now()->subMinutes(max(60, (int) config('files.orphan_grace_minutes')))->getTimestamp();
        $artifacts = $this->storage->agedArtifacts($cutoff, $limit, $afterArtifact);

        foreach ($artifacts as $artifact) {
            $reference = basename($artifact->relativePath);

            try {
                $result = $this->reconcileArtifact($artifact, $cutoff, $repair);

                if ($result !== null) {
                    $issues[] = ['kind' => $artifact->temporary ? 'temporary' : 'object', 'reference' => $reference, 'result' => $result];
                }
            } catch (StorageBusy) {
                $issues[] = ['kind' => 'artifact', 'reference' => $reference, 'result' => 'Busy; deferred to a later pass.'];
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
                $issues[] = ['kind' => 'artifact', 'reference' => $reference, 'result' => 'Inspection or repair failed; see server logs.'];
            }
        }

        return new ReconciliationReport(
            $issues, $accounts->count(), $files->count(), count($artifacts), $failed,
            $accounts->count() < $limit ? 0 : $accounts->last()->user_id,
            $files->count() < $limit ? 0 : $files->last()->id,
            count($artifacts) < $limit ? '' : $artifacts[array_key_last($artifacts)]->relativePath,
        );
    }

    private function reconcileAccount(StorageAccount $snapshot, bool $repair): ?string
    {
        return DB::transaction(function () use ($snapshot, $repair): ?string {
            $account = $this->accounts->lockForUser($snapshot->user_id);
            $totals = $this->files->chargedBytesForUser($account->user_id);

            if ($account->quota_bytes < $totals['used'] + $totals['reserved']) {
                return 'Metadata charges exceed the allowance; manual investigation required.';
            }

            if ($account->used_bytes === $totals['used'] && $account->reserved_bytes === $totals['reserved']) {
                return null;
            }

            if (! $repair) {
                return 'Counter mismatch: expected used='.$totals['used'].', reserved='.$totals['reserved'].' bytes.';
            }

            $before = ['used_bytes' => $account->used_bytes, 'reserved_bytes' => $account->reserved_bytes];
            $this->accounts->setCharges($account, $totals['used'], $totals['reserved']);
            $user = $account->user;
            $this->audit->write(null, 'storage.counters_reconciled', 'user', $user->public_id, $user->organisation_id, [
                'storage_before' => $before,
                'storage_after' => ['used_bytes' => $totals['used'], 'reserved_bytes' => $totals['reserved']],
            ]);

            return 'Storage counters repaired from charged file metadata.';
        }, 3);
    }

    private function reconcileFile(StoredFile $snapshot, bool $repair): ?string
    {
        $result = $this->storage->withExclusiveLock($snapshot->storage_key, function () use ($snapshot, $repair): ?array {
            $file = $this->files->findInternal($snapshot->id);

            if ($file === null || $file->status === FileStatus::Receiving) {
                return null;
            }

            $artifact = $this->storage->inspect($file->storage_key);

            if (! $repair) {
                $issue = $this->fileDiscrepancy($file, $artifact);

                return $issue === null ? null : ['issue' => $issue, 'action' => null];
            }

            return DB::transaction(function () use ($file, $artifact): ?array {
                $this->accounts->lockForUser($file->owner_id);
                $current = $this->files->lockInternal($file->id);
                $issue = $this->fileDiscrepancy($current, $artifact);

                if ($issue === null || $current->status === FileStatus::Receiving) {
                    return null;
                }

                if (in_array($issue, ['available_reserved', 'scan_required'], true)) {
                    $current->status = FileStatus::Quarantined;
                    $current->scan_attempts = 0;
                    $current->scanned_at = null;
                    $current->scanner_name = null;
                    $current->scanner_version = null;
                    $current->signature_version = null;
                    $action = 'scan';
                } else {
                    $current->status = FileStatus::Deleting;
                    $current->deleted_at = null;
                    $current->delete_requested_at ??= now();
                    $action = 'delete';
                }

                $current->lease_token = null;
                $current->lease_expires_at = null;
                $current->failure_code = in_array($issue, ['object_missing', 'integrity_mismatch'], true) ? $issue : null;
                $this->files->save($current);
                $this->audit->write(null, 'file.reconciliation_requested', 'file', $current->public_id, $current->organisation_id, ['reason' => $issue]);

                return ['issue' => $issue, 'action' => $action];
            }, 3);
        });

        if ($result === null) {
            return null;
        }

        if ($result['action'] === 'scan') {
            ScanStoredFile::dispatch($snapshot->id);
        } elseif ($result['action'] === 'delete') {
            DeleteStoredFile::dispatch($snapshot->id);
        }

        return $result['issue'].($repair ? ': repair queued; quota remains charged until processing completes.' : ': reported only.');
    }

    private function fileDiscrepancy(StoredFile $file, ?StorageArtifact $artifact): ?string
    {
        if ($file->status === FileStatus::Deleted) {
            return $file->quota_state === QuotaState::Released ? null : 'charged_tombstone';
        }

        if ($file->quota_state === QuotaState::Released) {
            return 'uncharged_live_file';
        }

        if ($artifact === null) {
            return 'object_missing';
        }

        if ($artifact->sizeBytes !== $file->size_bytes) {
            return 'integrity_mismatch';
        }

        if ($file->delete_requested_at !== null && $file->status !== FileStatus::Deleting) {
            return 'deletion_incomplete';
        }

        if ($file->status === FileStatus::Available && ! $file->hasSuccessfulScan()) {
            return 'scan_required';
        }

        return $file->status === FileStatus::Available && $file->quota_state === QuotaState::Reserved ? 'available_reserved' : null;
    }

    private function reconcileArtifact(StorageArtifact $artifact, int $cutoff, bool $repair): ?string
    {
        return $this->storage->withExclusiveLock($artifact->storageKey, function () use ($artifact, $cutoff, $repair): ?string {
            $file = $this->files->findByStorageKeyInternal($artifact->storageKey);

            if ($file !== null && ($file->status !== FileStatus::Deleted || $file->quota_state !== QuotaState::Released)) {
                return null;
            }

            if (! $repair) {
                return 'Aged unreferenced artifact; reported only.';
            }

            $publicId = $file->public_id ?? basename($artifact->storageKey);
            $this->audit->write(null, 'storage.orphan_cleanup_requested', 'file', $publicId, $file?->organisation_id);

            if (! $this->storage->deleteAgedArtifact($artifact, $cutoff)) {
                return 'Artifact disappeared or changed; removal deferred.';
            }

            $this->audit->write(null, 'storage.orphan_removed', 'file', $publicId, $file?->organisation_id);

            return 'Aged unreferenced artifact removed under its object lock.';
        });
    }
}
