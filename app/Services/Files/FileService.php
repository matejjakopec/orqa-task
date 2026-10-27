<?php

declare(strict_types=1);

namespace App\Services\Files;

use App\Contracts\Audit\AuditWriterInterface;
use App\Contracts\Files\FileProcessingServiceInterface;
use App\Contracts\Files\FileRepositoryInterface;
use App\Contracts\Files\FileServiceInterface;
use App\Contracts\Files\FileStorageInterface;
use App\Contracts\Files\QuotaServiceInterface;
use App\Contracts\Files\StorageAccountRepositoryInterface;
use App\Contracts\Users\UserRepositoryInterface;
use App\Data\Files\FileDownload;
use App\Data\Files\FileListCriteria;
use App\Data\Files\FileStatusSnapshot;
use App\Data\Files\IncomingFile;
use App\Data\Files\StorageSummary;
use App\Enums\FileStatus;
use App\Exceptions\StorageUnavailable;
use App\Exceptions\StoredObjectMissing;
use App\Jobs\DeleteStoredFile;
use App\Jobs\ScanStoredFile;
use App\Models\StorageAccount;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\FilePresentation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

final class FileService implements FileServiceInterface
{
    public function __construct(
        private FileRepositoryInterface $files,
        private StorageAccountRepositoryInterface $accounts,
        private UserRepositoryInterface $users,
        private QuotaServiceInterface $quota,
        private FileStorageInterface $storage,
        private FileProcessingServiceInterface $processing,
        private AuditWriterInterface $audit,
    ) {}

    /** @return LengthAwarePaginator<int, StoredFile> */
    public function paginate(User $actor, FileListCriteria $criteria): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', StoredFile::class);

        return $this->files->paginateVisibleTo($actor, $criteria);
    }

    public function storageSummary(User $actor): ?StorageAccount
    {
        Gate::forUser($actor)->authorize('viewAny', StoredFile::class);

        return Gate::forUser($actor)->allows('create', StoredFile::class) ? $this->accounts->findForUser($actor->id) : null;
    }

    public function upload(User $actor, IncomingFile $incoming): StoredFile
    {
        Gate::forUser($actor)->authorize('create', StoredFile::class);

        if ($incoming->sizeBytes > config('files.max_upload_bytes')) {
            throw ValidationException::withMessages(['file' => __('files.too_large')]);
        }

        $file = DB::transaction(function () use ($actor, $incoming): StoredFile {
            $account = $this->accounts->lockForUser($actor->id);
            $currentActor = $this->users->lockActor($actor);
            Gate::forUser($currentActor)->authorize('create', StoredFile::class);
            $this->quota->reserve($account, $incoming->sizeBytes);
            $file = $this->files->createReceiving($currentActor, $incoming, 'objects/'.Str::uuid());
            $this->audit->write($currentActor, 'file.upload_started', 'file', $file->public_id, $file->organisation_id);

            return $file;
        }, 3);

        try {
            $result = $this->storage->withExclusiveLock($file->storage_key, function () use ($file, $incoming): StoredFile {
                $current = $this->files->findInternal($file->id);

                if ($current->status !== FileStatus::Receiving) {
                    return $current;
                }

                $object = $this->storage->store($file->storage_key, $incoming);

                return DB::transaction(function () use ($file, $object): StoredFile {
                    $this->accounts->lockForUser($file->owner_id);
                    $current = $this->files->lockInternal($file->id);

                    if ($current->status !== FileStatus::Receiving) {
                        return $current;
                    }

                    if ($object->sizeBytes !== $current->size_bytes || preg_match('/\A[a-f0-9]{64}\z/', $object->sha256) !== 1) {
                        throw new StorageUnavailable('The completed object has invalid integrity metadata.');
                    }

                    $current->sha256 = $object->sha256;
                    $current->status = FileStatus::Quarantined;
                    $this->files->save($current);
                    $this->audit->write(null, 'file.quarantined', 'file', $current->public_id, $current->organisation_id);

                    return $current;
                }, 3);
            });
        } catch (Throwable $exception) {
            try {
                DB::transaction(function () use ($file): void {
                    $this->accounts->lockForUser($file->owner_id);
                    $current = $this->files->lockInternal($file->id);

                    if ($current->status === FileStatus::Receiving) {
                        $this->markDeleting($current, 'write_failed');
                    }
                }, 3);
                $this->processing->delete($file->id);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
                $this->queueDeletion($file->id);
            }

            throw new StorageUnavailable('The upload could not be completed.', previous: $exception);
        }

        if ($result->status === FileStatus::Deleting) {
            $this->queueDeletion($result->id);
        } elseif ($result->status === FileStatus::Quarantined) {
            try {
                ScanStoredFile::dispatch($result->id);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $result;
    }

    /** @param list<string> $publicIds */
    public function statuses(User $actor, array $publicIds): FileStatusSnapshot
    {
        Gate::forUser($actor)->authorize('viewAny', StoredFile::class);

        if (count($publicIds) < 1 || count($publicIds) > 5) {
            throw new InvalidArgumentException('One to five file references are required.');
        }

        foreach ($publicIds as $publicId) {
            if (! Str::isUuid($publicId)) {
                throw new InvalidArgumentException('Valid public file references are required.');
            }
        }

        $files = [];

        foreach ($this->files->statusesVisibleTo($actor, $publicIds) as $file) {
            $status = FilePresentation::displayStatus($file);
            $removed = $file->status === FileStatus::Deleted || $file->deleted_at !== null;
            $files[] = [
                'public_id' => $file->public_id, 'status' => $status->value,
                'label' => __('files.status_'.$status->value),
                'variant' => FilePresentation::statusVariant($status),
                'reason' => FilePresentation::failureMessage($file->failure_code),
                'pending' => FilePresentation::isPending($file), 'removed' => $removed,
                'download_url' => Gate::forUser($actor)->allows('download', $file) ? route('files.download', $file->public_id) : null,
                'delete_url' => ! $removed && $file->status !== FileStatus::Deleting && Gate::forUser($actor)->allows('delete', $file) ? route('files.delete', $file->public_id) : null,
            ];
        }

        $account = $this->storageSummary($actor);

        return new FileStatusSnapshot($files, $account === null ? null : StorageSummary::fromAccount($account));
    }

    public function prepareDownload(User $actor, string $publicId): FileDownload
    {
        $actor = $this->users->findOwnProfile($actor);
        $file = $this->files->findVisibleTo($actor, $publicId)
            ?? throw (new ModelNotFoundException)->setModel(StoredFile::class, [$publicId]);

        return $this->storage->withExclusiveLock($file->storage_key, function () use ($actor, $publicId): FileDownload {
            $file = $this->files->findVisibleTo($actor, $publicId)
                ?? throw (new ModelNotFoundException)->setModel(StoredFile::class, [$publicId]);
            Gate::forUser($actor)->authorize('download', $file);

            try {
                $stream = $this->storage->openRead($file->storage_key);
            } catch (StoredObjectMissing $exception) {
                $this->audit->write($actor, 'file.object_missing', 'file', $file->public_id, $file->organisation_id);
                report($exception);
                abort(404);
            }

            try {
                $stream->verify($file->size_bytes, $file->sha256);
                $this->audit->write($actor, 'file.download_started', 'file', $file->public_id, $file->organisation_id);

                return new FileDownload($stream, $file->original_name, $file->size_bytes);
            } catch (Throwable $exception) {
                $stream->close();

                throw $exception;
            }
        });
    }

    public function findForDeletion(User $actor, string $publicId): StoredFile
    {
        $file = $this->files->findForDeletionVisibleTo($actor, $publicId)
            ?? throw (new ModelNotFoundException)->setModel(StoredFile::class, [$publicId]);
        Gate::forUser($actor)->authorize('delete', $file);

        return $file;
    }

    public function requestDeletion(User $actor, string $publicId): void
    {
        $file = $this->findForDeletion($actor, $publicId);

        DB::transaction(function () use ($actor, $file): void {
            $this->accounts->lockForUser($file->owner_id);
            $currentActor = $this->users->lockActor($actor);
            $current = $this->files->lockInternal($file->id);
            Gate::forUser($currentActor)->authorize('delete', $current);

            if ($current->status === FileStatus::Deleted) {
                return;
            }

            if ($current->status !== FileStatus::Deleting) {
                $this->markDeleting($current);
                $this->audit->write($currentActor, 'file.delete_requested', 'file', $current->public_id, $current->organisation_id);
            }

            DB::afterCommit(fn () => $this->queueDeletion($current->id));
        }, 3);
    }

    private function markDeleting(StoredFile $file, ?string $failureCode = null): void
    {
        $file->status = FileStatus::Deleting;
        $file->delete_requested_at = now();
        $file->failure_code = $failureCode;
        $file->lease_token = null;
        $file->lease_expires_at = null;
        $this->files->save($file);
    }

    private function queueDeletion(int $fileId): void
    {
        try {
            DeleteStoredFile::dispatch($fileId);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
