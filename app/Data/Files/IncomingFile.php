<?php

declare(strict_types=1);

namespace App\Data\Files;

use App\Exceptions\StorageUnavailable;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final readonly class IncomingFile
{
    private function __construct(
        public string $temporaryPath,
        public string $originalName,
        public ?string $extension,
        public string $mimeType,
        public int $sizeBytes,
    ) {}

    public static function fromUpload(UploadedFile $upload): self
    {
        $name = $upload->getClientOriginalPath();

        if (! $upload->isValid() || ! mb_check_encoding($name, 'UTF-8') || strlen($name) > 255
            || trim($name) === '' || in_array(trim($name), ['.', '..'], true)
            || preg_match('/[\p{C}\/\\\\]/u', $name) !== 0) {
            throw ValidationException::withMessages(['file' => __('files.invalid_name')]);
        }

        $path = $upload->getRealPath();
        $size = $path === false ? false : filesize($path);

        if ($size === false) {
            throw new StorageUnavailable('The temporary upload cannot be read.');
        }

        if ($size > config('files.max_upload_bytes')) {
            throw ValidationException::withMessages(['file' => __('files.too_large')]);
        }

        $extension = mb_strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return new self(
            $path, $name,
            preg_match('/\A[a-z0-9]{1,50}\z/', $extension) === 1 ? $extension : null,
            $upload->getMimeType() ?? 'application/octet-stream', $size,
        );
    }
}
