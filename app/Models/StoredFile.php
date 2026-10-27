<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FileStatus;
use App\Enums\QuotaState;
use Database\Factories\StoredFileFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StoredFile extends Model
{
    /** @use HasFactory<StoredFileFactory> */
    use HasFactory, HasUuids;

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<Organisation, $this> */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function hasSuccessfulScan(): bool
    {
        return $this->scanned_at !== null
            && $this->scanner_name === 'clamav'
            && is_string($this->scanner_version) && $this->scanner_version !== ''
            && is_string($this->signature_version) && preg_match('/\A[0-9]+\z/', $this->signature_version) === 1
            && is_string($this->sha256) && preg_match('/\A[a-f0-9]{64}\z/', $this->sha256) === 1;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'owner_id' => 'integer',
            'organisation_id' => 'integer',
            'size_bytes' => 'integer',
            'status' => FileStatus::class,
            'quota_state' => QuotaState::class,
            'scan_attempts' => 'integer',
            'lease_expires_at' => 'datetime',
            'scanned_at' => 'datetime',
            'delete_requested_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
