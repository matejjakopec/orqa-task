<?php

declare(strict_types=1);

namespace App\Contracts\Files;

use App\Data\Files\FileReadStream;
use App\Data\Files\IncomingFile;
use App\Data\Files\StorageArtifact;
use App\Data\Files\StoredObject;
use Closure;

interface FileStorageInterface
{
    /** @template T
     * @param  Closure(): T  $operation
     * @return T
     */
    public function withExclusiveLock(string $key, Closure $operation): mixed;

    public function store(string $key, IncomingFile $file): StoredObject;

    public function openRead(string $key): FileReadStream;

    public function deleteIfExists(string $key): void;

    public function inspect(string $key): ?StorageArtifact;

    /** @return list<StorageArtifact> */
    public function agedArtifacts(int $modifiedBefore, int $limit, string $afterPath): array;

    /** Must be called while holding this artifact's object lock. */
    public function deleteAgedArtifact(StorageArtifact $artifact, int $modifiedBefore): bool;
}
