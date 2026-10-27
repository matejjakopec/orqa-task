<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\FileStatus;
use App\Models\StoredFile;

final class FilePresentation
{
    public static function displayStatus(StoredFile $file): FileStatus
    {
        if ($file->deleted_at !== null) {
            return FileStatus::Deleted;
        }

        return $file->status === FileStatus::Available && ! $file->hasSuccessfulScan() ? FileStatus::Quarantined : $file->status;
    }

    public static function isPending(StoredFile $file): bool
    {
        return in_array(self::displayStatus($file), [FileStatus::Receiving, FileStatus::Quarantined, FileStatus::Scanning, FileStatus::Deleting, FileStatus::Rejected], true);
    }

    public static function statusVariant(FileStatus $status): string
    {
        return match ($status) {
            FileStatus::Available => 'success',
            FileStatus::Rejected => 'danger',
            FileStatus::Deleting, FileStatus::Deleted => 'secondary',
            default => 'warning',
        };
    }

    public static function failureMessage(?string $code): ?string
    {
        return $code === null ? null : __(match ($code) {
            'scanner_unavailable' => 'files.failure_scanner_unavailable',
            'encrypted_content' => 'files.failure_encrypted_content',
            'inspection_limit' => 'files.failure_inspection_limit',
            'malware_detected' => 'files.failure_malware_detected',
            'integrity_mismatch', 'object_missing' => 'files.failure_integrity',
            'write_failed', 'abandoned_upload' => 'files.failure_upload_incomplete',
            default => 'files.failure_inspection_unavailable',
        });
    }

    public static function icon(?string $extension): string
    {
        return match (mb_strtolower($extension ?? '')) {
            'pdf' => 'bi-file-earmark-pdf',
            'doc', 'docx', 'odt', 'rtf' => 'bi-file-earmark-word',
            'xls', 'xlsx', 'ods', 'csv' => 'bi-file-earmark-spreadsheet',
            'ppt', 'pptx', 'odp' => 'bi-file-earmark-slides',
            'zip', 'rar', '7z', 'gz', 'tar', 'bz2', 'xz' => 'bi-file-earmark-zip',
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'avif', 'heic' => 'bi-file-earmark-image',
            'mp3', 'wav', 'ogg', 'flac', 'm4a' => 'bi-file-earmark-music',
            'mp4', 'webm', 'mov', 'avi', 'mkv' => 'bi-file-earmark-play',
            'txt', 'md', 'log' => 'bi-file-earmark-text',
            'php', 'js', 'json', 'html', 'css', 'xml', 'sh', 'py' => 'bi-file-earmark-code',
            default => 'bi-file-earmark',
        };
    }
}
