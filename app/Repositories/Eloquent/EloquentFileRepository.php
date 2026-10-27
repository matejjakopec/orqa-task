<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Files\FileRepositoryInterface;
use App\Data\Files\FileListCriteria;
use App\Data\Files\IncomingFile;
use App\Enums\FileStatus;
use App\Enums\QuotaState;
use App\Enums\RoleName;
use App\Models\StoredFile;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class EloquentFileRepository implements FileRepositoryInterface
{
    /** @return LengthAwarePaginator<int, StoredFile> */
    public function paginateVisibleTo(User $actor, FileListCriteria $criteria): LengthAwarePaginator
    {
        $query = $this->visibleQuery($actor)->with(['owner', 'organisation']);

        if ($criteria->ownerId !== null) {
            $query->where('owner_id', $criteria->ownerId);
        }

        if ($criteria->organisationId !== null) {
            $query->where('organisation_id', $criteria->organisationId);
        }

        if ($criteria->search !== null) {
            $query->where('original_name', 'like', '%'.$criteria->search.'%');
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate(5, ['*'], 'page', $criteria->page);
    }

    public function findVisibleTo(User $actor, string $publicId): ?StoredFile
    {
        if (! Str::isUuid($publicId)) {
            return null;
        }

        return $this->visibleQuery($actor)->where('public_id', $publicId)->first();
    }

    public function findForDeletionVisibleTo(User $actor, string $publicId): ?StoredFile
    {
        if (! Str::isUuid($publicId)) {
            return null;
        }

        return $this->visibleQuery($actor, includeDeleted: true)->where('public_id', $publicId)->first();
    }

    public function createReceiving(User $owner, IncomingFile $incoming, string $key): StoredFile
    {
        $file = new StoredFile;
        $file->forceFill([
            'owner_id' => $owner->id, 'organisation_id' => $owner->organisation_id,
            'storage_key' => $key, 'original_name' => $incoming->originalName,
            'extension' => $incoming->extension, 'mime_type' => $incoming->mimeType,
            'size_bytes' => $incoming->sizeBytes, 'status' => FileStatus::Receiving,
            'quota_state' => QuotaState::Reserved,
        ])->save();

        return $file;
    }

    /** @param list<string> $publicIds
     * @return Collection<int, StoredFile>
     */
    public function statusesVisibleTo(User $actor, array $publicIds): Collection
    {
        return $this->visibleQuery($actor, includeDeleted: true)->whereIn('public_id', $publicIds)->limit(5)->get();
    }

    public function findInternal(int $id): ?StoredFile
    {
        return StoredFile::query()->find($id);
    }

    public function lockInternal(int $id): StoredFile
    {
        return StoredFile::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function save(StoredFile $file): void
    {
        $file->save();
    }

    /** @return Collection<int, StoredFile> */
    public function recoveryCandidates(CarbonInterface $staleBefore, int $limit, int $afterId = 0): Collection
    {
        return StoredFile::query()->where(function (Builder $query) use ($staleBefore): void {
            $query->whereIn('status', [FileStatus::Deleting->value, FileStatus::Rejected->value, FileStatus::Quarantined->value])
                ->orWhere(fn (Builder $receiving): Builder => $receiving->where('status', FileStatus::Receiving->value)->where('updated_at', '<=', $staleBefore))
                ->orWhere(fn (Builder $scanning): Builder => $scanning->where('status', FileStatus::Scanning->value)->where(fn (Builder $lease): Builder => $lease->whereNull('lease_expires_at')->orWhere('lease_expires_at', '<=', now())))
                ->orWhere(fn (Builder $legacy): Builder => $legacy->where('status', FileStatus::Available->value)->where(fn (Builder $scan): Builder => $scan->whereNull('scanned_at')->orWhereNull('scanner_version')->orWhereNull('signature_version')->orWhereNull('scanner_name')->orWhere('scanner_name', '!=', 'clamav')));
        })->where('id', '>', $afterId)->orderBy('id')->limit($limit)->get();
    }

    /** @return Collection<int, StoredFile> */
    public function reconciliationCandidates(int $afterId, int $limit): Collection
    {
        return StoredFile::query()->where('id', '>', $afterId)
            ->where(fn (Builder $query): Builder => $query->where('status', '!=', FileStatus::Deleted->value)->orWhere('quota_state', '!=', QuotaState::Released->value))
            ->orderBy('id')->limit($limit)->get();
    }

    public function findByStorageKeyInternal(string $key): ?StoredFile
    {
        return StoredFile::query()->where('storage_key', $key)->first();
    }

    /** @return array{used: int, reserved: int} */
    public function chargedBytesForUser(int $userId): array
    {
        $totals = StoredFile::query()->where('owner_id', $userId)->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN quota_state = ? THEN size_bytes ELSE 0 END), 0) AS used_bytes, COALESCE(SUM(CASE WHEN quota_state = ? THEN size_bytes ELSE 0 END), 0) AS reserved_bytes', [QuotaState::Used->value, QuotaState::Reserved->value])
            ->first();

        return ['used' => (int) $totals->used_bytes, 'reserved' => (int) $totals->reserved_bytes];
    }

    /** @return list<array{status: string, count: int, oldest_pending_at: string}> */
    public function processingSummary(): array
    {
        $summary = StoredFile::query()->whereIn('status', [FileStatus::Receiving, FileStatus::Quarantined, FileStatus::Scanning, FileStatus::Rejected, FileStatus::Deleting])
            ->toBase()->select('status')
            ->selectRaw('COUNT(*) AS file_count, MIN(CASE WHEN status = ? THEN COALESCE(delete_requested_at, updated_at) ELSE created_at END) AS oldest_pending_at', [FileStatus::Deleting->value])
            ->groupBy('status')->orderBy('status')->get()
            ->map(fn (object $row): array => ['status' => (string) $row->status, 'count' => (int) $row->file_count, 'oldest_pending_at' => (string) $row->oldest_pending_at])->all();

        return array_values($summary);
    }

    /** @return Builder<StoredFile> */
    private function visibleQuery(User $actor, bool $includeDeleted = false): Builder
    {
        $query = StoredFile::query();
        $actor->loadMissing('roles');

        if (! Gate::forUser($actor)->allows('viewAny', StoredFile::class)) {
            return $query->whereRaw('1 = 0');
        }

        if (! $actor->hasRole(RoleName::Superadmin)) {
            $query->where('owner_id', $actor->id)->where('organisation_id', $actor->organisation_id);
        }

        if (! $includeDeleted) {
            $query->where('status', '!=', FileStatus::Deleted->value)->whereNull('deleted_at');
        }

        return $query;
    }
}
