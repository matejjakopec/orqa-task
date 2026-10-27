<?php

declare(strict_types=1);

namespace App\Data\Files;

use App\Exceptions\StorageUnavailable;
use Generator;
use InvalidArgumentException;

final class FileReadStream
{
    /** @var resource|null */
    private mixed $stream;

    /** @param resource $stream */
    public function __construct(mixed $stream)
    {
        if (! is_resource($stream)) {
            throw new InvalidArgumentException('An open stream is required.');
        }

        $this->stream = $stream;
    }

    public function verify(int $sizeBytes, string $sha256): void
    {
        $hash = hash_init('sha256');
        $size = hash_update_stream($hash, $this->stream);

        if ($size !== $sizeBytes || ! hash_equals($sha256, hash_final($hash)) || ! rewind($this->stream)) {
            throw new StorageUnavailable('Stored object integrity does not match its metadata.');
        }
    }

    public function send(): void
    {
        fpassthru($this->stream);
    }

    /** @return Generator<int, string> */
    public function chunks(): Generator
    {
        if (! is_resource($this->stream) || ! rewind($this->stream)) {
            throw new StorageUnavailable('The stored object stream is not readable.');
        }

        while (! feof($this->stream)) {
            $chunk = fread($this->stream, 65536);

            if ($chunk === false || ($chunk === '' && ! feof($this->stream))) {
                throw new StorageUnavailable('The stored object stream could not be read completely.');
            }

            if ($chunk !== '') {
                yield $chunk;
            }
        }
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->stream = null;
    }

    public function __destruct()
    {
        $this->close();
    }
}
