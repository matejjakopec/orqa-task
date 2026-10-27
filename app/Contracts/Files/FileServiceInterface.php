<?php

declare(strict_types=1);

namespace App\Contracts\Files;

use App\Data\Files\FileDownload;
use App\Data\Files\FileListCriteria;
use App\Data\Files\FileStatusSnapshot;
use App\Data\Files\IncomingFile;
use App\Models\StorageAccount;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FileServiceInterface
{
    /** @return LengthAwarePaginator<int, StoredFile> */
    public function paginate(User $actor, FileListCriteria $criteria): LengthAwarePaginator;

    public function storageSummary(User $actor): ?StorageAccount;

    /** @param list<string> $publicIds */
    public function statuses(User $actor, array $publicIds): FileStatusSnapshot;

    public function upload(User $actor, IncomingFile $incoming): StoredFile;

    public function prepareDownload(User $actor, string $publicId): FileDownload;

    public function findForDeletion(User $actor, string $publicId): StoredFile;

    public function requestDeletion(User $actor, string $publicId): void;
}
