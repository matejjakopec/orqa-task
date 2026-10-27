<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Contracts\Files\FileStorageInterface;
use App\Data\Files\FileReadStream;
use App\Data\Files\IncomingFile;
use App\Data\Files\StorageArtifact;
use App\Data\Files\StoredObject;
use App\Exceptions\StorageBusy;
use App\Exceptions\StorageUnavailable;
use App\Exceptions\StoredObjectMissing;
use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use SplMaxHeap;

final class LaravelFileStorage implements FileStorageInterface
{
    public function withExclusiveLock(string $key, Closure $operation): mixed
    {
        $id = $this->identifier($key);
        $this->prepareDirectories();
        $lock = @fopen($this->path('locks/'.$id.'.lock'), 'c');

        if ($lock === false) {
            throw new StorageUnavailable('The object lock cannot be opened.');
        }

        try {
            if (! @chmod($this->path('locks/'.$id.'.lock'), 0600)) {
                throw new StorageUnavailable('The object lock permissions cannot be set.');
            }

            $wouldBlock = 0;

            if (! @flock($lock, LOCK_EX | LOCK_NB, $wouldBlock)) {
                throw $wouldBlock === 1
                    ? new StorageBusy('The stored object is currently in use.')
                    : new StorageUnavailable('The stored object lock is unavailable.');
            }

            return $operation();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function store(string $key, IncomingFile $file): StoredObject
    {
        $id = $this->identifier($key);
        $this->prepareDirectories();
        $temporary = $this->path('temporary/'.$id.'.part');
        $destination = $this->path($key);

        if ($this->exists($destination)) {
            throw new StorageUnavailable('An immutable object already exists.');
        }

        $input = @fopen($file->temporaryPath, 'rb');

        if ($input === false) {
            throw new StorageUnavailable('The temporary upload cannot be opened.');
        }

        $output = null;

        try {
            $output = @fopen($temporary, 'xb');

            if ($output === false || ! @chmod($temporary, 0600)) {
                throw new StorageUnavailable('The private temporary object cannot be created.');
            }

            $hash = hash_init('sha256');
            $size = 0;

            while (! feof($input)) {
                $chunk = fread($input, 1024 * 1024);

                if ($chunk === false || ($chunk === '' && ! feof($input))) {
                    throw new StorageUnavailable('The temporary upload cannot be read completely.');
                }

                $length = strlen($chunk);
                $size += $length;

                if ($size > $file->sizeBytes) {
                    throw new StorageUnavailable('The upload size changed during the write.');
                }

                hash_update($hash, $chunk);
                $offset = 0;

                while ($offset < $length) {
                    $written = fwrite($output, substr($chunk, $offset));

                    if ($written === false || $written === 0) {
                        throw new StorageUnavailable('The private object write was incomplete.');
                    }

                    $offset += $written;
                }
            }

            if ($size !== $file->sizeBytes || ! fflush($output) || ! fsync($output)) {
                throw new StorageUnavailable('The private object could not be persisted completely.');
            }

            fclose($output);
            $output = null;

            if (! @rename($temporary, $destination)) {
                throw new StorageUnavailable('The private object could not be published.');
            }

            return new StoredObject($size, hash_final($hash));
        } finally {
            fclose($input);

            if (is_resource($output)) {
                fclose($output);
            }
        }
    }

    public function openRead(string $key): FileReadStream
    {
        $this->identifier($key);
        $this->prepareDirectories();
        $path = $this->path($key);

        if (! $this->exists($path)) {
            throw new StoredObjectMissing('The private object is missing.');
        }

        $stream = @fopen($path, 'rb');

        if ($stream === false) {
            throw new StorageUnavailable('The private object cannot be opened.');
        }

        return new FileReadStream($stream);
    }

    public function deleteIfExists(string $key): void
    {
        $id = $this->identifier($key);
        $this->prepareDirectories();

        foreach ([$this->path($key), $this->path('temporary/'.$id.'.part')] as $path) {
            if ($this->exists($path) && ! @unlink($path)) {
                throw new StorageUnavailable('A private object cannot be removed.');
            }

            if ($this->exists($path)) {
                throw new StorageUnavailable('Private object absence cannot be confirmed.');
            }
        }
    }

    public function inspect(string $key): ?StorageArtifact
    {
        $this->identifier($key);
        $this->prepareDirectories();

        return $this->inspectArtifact($key, $key, false);
    }

    /** @return list<StorageArtifact> */
    public function agedArtifacts(int $modifiedBefore, int $limit, string $afterPath): array
    {
        $this->prepareDirectories();
        /** @var SplMaxHeap<string> $paths */
        $paths = new SplMaxHeap;

        foreach (['objects' => '', 'temporary' => '.part'] as $relative => $suffix) {
            $directory = @opendir($this->path($relative));

            if ($directory === false) {
                throw new StorageUnavailable('The vault directory cannot be inspected.');
            }

            try {
                while (($entry = readdir($directory)) !== false) {
                    $id = $suffix === '' ? $entry : (str_ends_with($entry, $suffix) ? substr($entry, 0, -strlen($suffix)) : '');
                    $path = $relative.'/'.$entry;

                    if (! Str::isUuid($id) || $id !== mb_strtolower($id) || strcmp($path, $afterPath) <= 0) {
                        continue;
                    }

                    $artifact = $this->inspectArtifact($path, 'objects/'.$id, $suffix !== '');

                    if ($artifact === null || $artifact->modifiedAt > $modifiedBefore) {
                        continue;
                    }

                    if ($paths->count() < $limit || strcmp($path, $paths->top()) < 0) {
                        $paths->insert($path);

                        if ($paths->count() > $limit) {
                            $paths->extract();
                        }
                    }
                }
            } finally {
                closedir($directory);
            }
        }

        $artifacts = [];

        foreach ($paths as $path) {
            $temporary = str_starts_with($path, 'temporary/');
            $id = $temporary ? substr(basename($path), 0, -5) : basename($path);
            $artifact = $this->inspectArtifact($path, 'objects/'.$id, $temporary);

            if ($artifact !== null) {
                $artifacts[] = $artifact;
            }
        }

        usort($artifacts, fn (StorageArtifact $left, StorageArtifact $right): int => strcmp($left->relativePath, $right->relativePath));

        return $artifacts;
    }

    public function deleteAgedArtifact(StorageArtifact $artifact, int $modifiedBefore): bool
    {
        $id = $this->identifier($artifact->storageKey);
        $expectedPath = $artifact->temporary ? 'temporary/'.$id.'.part' : $artifact->storageKey;

        if ($artifact->relativePath !== $expectedPath) {
            throw new InvalidArgumentException('A generated artifact path is required.');
        }

        $current = $this->inspectArtifact($expectedPath, $artifact->storageKey, $artifact->temporary);

        if ($current === null || $current->modifiedAt > $modifiedBefore
            || $current->modifiedAt !== $artifact->modifiedAt || $current->sizeBytes !== $artifact->sizeBytes) {
            return false;
        }

        $path = $this->path($expectedPath);

        if (! @unlink($path) || $this->exists($path)) {
            throw new StorageUnavailable('The aged private artifact cannot be removed.');
        }

        return true;
    }

    private function inspectArtifact(string $relativePath, string $key, bool $temporary): ?StorageArtifact
    {
        $path = $this->path($relativePath);

        if (! $this->exists($path)) {
            return null;
        }

        $stat = @lstat($path);

        if ($stat === false || ($stat['mode'] & 0170000) !== 0100000) {
            throw new StorageUnavailable('A vault artifact is not an inspectable regular file.');
        }

        return new StorageArtifact($relativePath, $key, $stat['mtime'], $stat['size'], $temporary);
    }

    private function identifier(string $key): string
    {
        $id = substr($key, strlen('objects/'));

        if (! str_starts_with($key, 'objects/') || ! Str::isUuid($id) || $id !== mb_strtolower($id)) {
            throw new InvalidArgumentException('A generated object key is required.');
        }

        return $id;
    }

    private function path(string $relative): string
    {
        if (config('filesystems.disks.vault.driver') !== 'local') {
            throw new LogicException('The vault requires a shared local filesystem.');
        }

        return Storage::disk('vault')->path($relative);
    }

    private function prepareDirectories(): void
    {
        foreach (['objects', 'temporary', 'locks'] as $relative) {
            $path = $this->path($relative);

            if (! is_dir($path) && ! @mkdir($path, 0700, true) && ! is_dir($path)) {
                throw new StorageUnavailable('The vault directory cannot be created.');
            }

            if (! is_readable($path) || ! is_writable($path)) {
                throw new StorageUnavailable('The vault directory is unavailable.');
            }
        }
    }

    private function exists(string $path): bool
    {
        clearstatcache(true, $path);

        if (@lstat($path) !== false) {
            return true;
        }

        $directory = @opendir(dirname($path));

        if ($directory === false) {
            throw new StorageUnavailable('The vault directory cannot be inspected.');
        }

        try {
            while (($entry = readdir($directory)) !== false) {
                if ($entry === basename($path)) {
                    return true;
                }
            }

            return false;
        } finally {
            closedir($directory);
        }
    }
}
