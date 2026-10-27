<?php

declare(strict_types=1);

namespace App\Contracts\Files;

use App\Data\Files\FileListCriteria;
use App\Data\Files\IncomingFile;
use App\Models\StoredFile;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface FileRepositoryInterface
{
    /** @return LengthAwarePaginator<int, StoredFile> */
    public function paginateVisibleTo(User $actor, FileListCriteria $criteria): LengthAwarePaginator;

    public function findVisibleTo(User $actor, string $publicId): ?StoredFile;

    public function findForDeletionVisibleTo(User $actor, string $publicId): ?StoredFile;

    /** @param list<string> $publicIds
     * @return Collection<int, StoredFile>
     */
    public function statusesVisibleTo(User $actor, array $publicIds): Collection;

    public function createReceiving(User $owner, IncomingFile $incoming, string $key): StoredFile;

    public function findInternal(int $id): ?StoredFile;

    public function lockInternal(int $id): StoredFile;

    public function save(StoredFile $file): void;

    /** @return Collection<int, StoredFile> */
    public function recoveryCandidates(CarbonInterface $staleBefore, int $limit, int $afterId = 0): Collection;

    /** @return Collection<int, StoredFile> */
    public function reconciliationCandidates(int $afterId, int $limit): Collection;

    public function findByStorageKeyInternal(string $key): ?StoredFile;

    /** @return array{used: int, reserved: int} */
    public function chargedBytesForUser(int $userId): array;

    /** @return list<array{status: string, count: int, oldest_pending_at: string}> */
    public function processingSummary(): array;
}
