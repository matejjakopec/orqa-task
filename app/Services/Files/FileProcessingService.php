<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Audit\AuditWriterInterface;
use App\Contracts\Files\FileProcessingServiceInterface;
use App\Contracts\Files\FileRepositoryInterface;
use App\Contracts\Files\FileStorageInterface;
use App\Contracts\Files\MalwareScannerInterface;
use App\Contracts\Files\QuotaServiceInterface;
use App\Contracts\Files\StorageAccountRepositoryInterface;
use App\Data\Files\ScanResult;
use App\Enums\FileStatus;
use App\Enums\ScanVerdict;
use App\Exceptions\ScannerUnavailable;
use App\Exceptions\StoredObjectMissing;
use App\Jobs\DeleteStoredFile;
use App\Jobs\ScanStoredFile;
use App\Models\StoredFile;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class FileProcessingService implements FileProcessingServiceInterface
{
    public function __construct(
        private FileRepositoryInterface $files,
        private StorageAccountRepositoryInterface $accounts,
        private QuotaServiceInterface $quota,
        private FileStorageInterface $storage,
        private AuditWriterInterface $audit,
        private MalwareScannerInterface $scanner,
    ) {}

    public function scan(int $fileId): ?int
    {
        $snapshot = $this->files->findInternal($fileId);

        if ($snapshot === null || ! in_array($snapshot->status, [FileStatus::Quarantined, FileStatus::Scanning], true)) {
            return null;
        }

        $delay = $this->storage->withExclusiveLock($snapshot->storage_key, function () use ($snapshot): ?int {
            $claim = DB::transaction(function () use ($snapshot): StoredFile|int|null {
                $this->accounts->lockForUser($snapshot->owner_id);
                $file = $this->files->lockInternal($snapshot->id);

                if (! in_array($file->status, [FileStatus::Quarantined, FileStatus::Scanning], true)) {
                    return null;
                }

                if ($file->status === FileStatus::Scanning && $file->lease_expires_at?->isFuture()) {
                    return max(1, (int) ceil(now()->diffInSeconds($file->lease_expires_at)) + 1);
                }

                if ($file->scan_attempts >= config('files.scanner.max_attempts')) {
                    $file->status = FileStatus::Rejected;
                    $file->failure_code = 'inspection_unavailable';
                    $file->lease_token = null;
                    $file->lease_expires_at = null;
                    $this->files->save($file);
                    $this->audit->write(null, 'file.scan_rejected', 'file', $file->public_id, $file->organisation_id);

                    return null;
                }

                $file->status = FileStatus::Scanning;
                $file->scan_attempts++;
                $file->lease_token = (string) Str::uuid();
                $file->lease_expires_at = now()->addSeconds(config('files.scanner.lease_seconds'));
                $file->failure_code = null;
                $this->files->save($file);

                return $file;
            }, 3);

            if (! $claim instanceof StoredFile) {
                return $claim;
            }

            $stream = null;

            try {
                $stream = $this->storage->openRead($claim->storage_key);
                $result = $this->scanner->scan($stream);
            } catch (StoredObjectMissing $exception) {
                report($exception);
                $result = new ScanResult(ScanVerdict::Rejected, 0, '', '', '', 'object_missing');
            } catch (Throwable $exception) {
                $retry = $this->finishScan($claim, new ScanResult(ScanVerdict::Unavailable, 0, '', '', '', 'scanner_unavailable'));

                if ($retry) {
                    throw new ScannerUnavailable('File inspection is temporarily unavailable.', previous: $exception);
                }

                report($exception);

                return null;
            } finally {
                $stream?->close();
            }

            if ($this->finishScan($claim, $result)) {
                throw new ScannerUnavailable('File inspection is temporarily unavailable.');
            }

            return null;
        });

        $current = $this->files->findInternal($fileId);

        if ($current !== null && in_array($current->status, [FileStatus::Rejected, FileStatus::Deleting], true)) {
            $this->queueCleanup($fileId);
        } elseif ($current?->status === FileStatus::Scanning && $delay === null) {
            return max(1, (int) ceil(now()->diffInSeconds($current->lease_expires_at ?? now())) + 1);
        }

        return $delay;
    }

    private function finishScan(StoredFile $claim, ScanResult $result): bool
    {
        return DB::transaction(function () use ($claim, $result): bool {
            $account = $this->accounts->lockForUser($claim->owner_id);
            $file = $this->files->lockInternal($claim->id);

            if ($file->status !== FileStatus::Scanning || $file->lease_token !== $claim->lease_token
                || ! $file->lease_expires_at?->isFuture()) {
                return false;
            }

            $file->lease_token = null;
            $file->lease_expires_at = null;
            $retry = false;

            if ($result->verdict === ScanVerdict::Unavailable) {
                $retry = $file->scan_attempts < config('files.scanner.max_attempts');
                $file->status = $retry ? FileStatus::Quarantined : FileStatus::Rejected;
                $file->failure_code = $retry ? 'scanner_unavailable' : 'inspection_unavailable';
            } elseif ($result->verdict === ScanVerdict::Clean && $result->sizeBytes === $file->size_bytes
                && is_string($file->sha256) && hash_equals($file->sha256, $result->sha256)
                && $result->engineVersion !== '' && preg_match('/\A[0-9]+\z/', $result->signatureVersion) === 1) {
                $this->quota->commit($account, $file);
                $file->status = FileStatus::Available;
                $file->scanned_at = now();
                $file->scanner_name = 'clamav';
                $file->scanner_version = $result->engineVersion;
                $file->signature_version = $result->signatureVersion;
                $file->failure_code = null;
            } else {
                $file->status = FileStatus::Rejected;
                $file->failure_code = $result->verdict === ScanVerdict::Clean ? 'integrity_mismatch'
                    : (in_array($result->failureCode, ['encrypted_content', 'inspection_limit', 'malware_detected', 'object_missing'], true) ? $result->failureCode : 'inspection_unavailable');
            }

            $this->files->save($file);
            $event = match ($file->status) {
                FileStatus::Available => 'file.scan_completed',
                FileStatus::Quarantined => 'file.scan_delayed',
                default => 'file.scan_rejected',
            };
            $this->audit->write(null, $event, 'file', $file->public_id, $file->organisation_id);

            return $retry;
        }, 3);
    }

    public function delete(int $fileId): void
    {
        $file = $this->files->findInternal($fileId);

        if ($file === null || $file->status === FileStatus::Deleted) {
            return;
        }

        $this->storage->withExclusiveLock($file->storage_key, function () use ($file): void {
            $this->prepareRejectedDeletion($file);
            $this->deleteUnderLock($file);
        });
    }

    public function recover(int $fileId, CarbonInterface $staleBefore): void
    {
        $file = $this->files->findInternal($fileId);

        if ($file === null || $file->status === FileStatus::Deleted) {
            return;
        }

        $this->storage->withExclusiveLock($file->storage_key, function () use ($file, $staleBefore): void {
            DB::transaction(function () use ($file, $staleBefore): void {
                $this->accounts->lockForUser($file->owner_id);
                $current = $this->files->lockInternal($file->id);

                if (($current->status === FileStatus::Available && ! $current->hasSuccessfulScan())
                    || ($current->status === FileStatus::Scanning && ! $current->lease_expires_at?->isFuture())) {
                    $current->status = FileStatus::Quarantined;
                    $current->lease_token = null;
                    $current->lease_expires_at = null;
                    $current->scanned_at = null;
                    $current->scanner_name = null;
                    $current->scanner_version = null;
                    $current->signature_version = null;
                    $this->files->save($current);
                    $this->audit->write(null, 'file.scan_required', 'file', $current->public_id, $current->organisation_id);

                    return;
                }

                if ($current->status !== FileStatus::Receiving || $current->updated_at->greaterThan($staleBefore)) {
                    return;
                }

                $current->status = FileStatus::Deleting;
                $current->delete_requested_at = now();
                $current->failure_code = 'abandoned_upload';
                $this->files->save($current);
                $this->audit->write(null, 'file.upload_abandoned', 'file', $current->public_id, $current->organisation_id);
            }, 3);

            $this->prepareRejectedDeletion($file);
            $this->deleteUnderLock($file);
        });

        if ($this->files->findInternal($fileId)?->status === FileStatus::Quarantined) {
            try {
                ScanStoredFile::dispatch($fileId);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function prepareRejectedDeletion(StoredFile $snapshot): void
    {
        DB::transaction(function () use ($snapshot): void {
            $this->accounts->lockForUser($snapshot->owner_id);
            $file = $this->files->lockInternal($snapshot->id);

            if ($file->status === FileStatus::Rejected) {
                $file->status = FileStatus::Deleting;
                $file->delete_requested_at = now();
                $this->files->save($file);
            }
        }, 3);
    }

    private function queueCleanup(int $fileId): void
    {
        try {
            DeleteStoredFile::dispatch($fileId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function deleteUnderLock(StoredFile $snapshot): void
    {
        $file = $this->files->findInternal($snapshot->id);

        if ($file === null || $file->status !== FileStatus::Deleting) {
            return;
        }

        $this->storage->deleteIfExists($file->storage_key);

        DB::transaction(function () use ($file): void {
            $account = $this->accounts->lockForUser($file->owner_id);
            $current = $this->files->lockInternal($file->id);

            if ($current->status !== FileStatus::Deleting) {
                return;
            }

            $this->quota->release($account, $current);
            $current->status = FileStatus::Deleted;
            $current->deleted_at = now();
            $current->lease_token = null;
            $current->lease_expires_at = null;
            $this->files->save($current);
            $this->audit->write(null, 'file.deleted', 'file', $current->public_id, $current->organisation_id);
        }, 3);
    }
}
