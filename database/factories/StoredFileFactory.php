<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FileStatus;
use App\Enums\QuotaState;
use App\Models\StorageAccount;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;

/** @extends Factory<StoredFile> */
final class StoredFileFactory extends Factory
{
    private const CONTENTS = "Harmless storage fixture.\n";

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->ordinaryUser(),
            'organisation_id' => fn (array $attributes): int => User::query()->findOrFail($attributes['owner_id'])->organisation_id,
            'storage_key' => 'objects/'.Str::uuid(),
            'original_name' => 'example.txt',
            'extension' => 'txt',
            'mime_type' => 'text/plain',
            'size_bytes' => strlen(self::CONTENTS),
            'sha256' => null,
            'status' => FileStatus::Receiving,
            'quota_state' => QuotaState::Reserved,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (StoredFile $file): void {
            if ($file->status === FileStatus::Available && ! app()->environment(['local', 'testing'])) {
                throw new LogicException('Available file fixtures are restricted to local and testing environments.');
            }
        })->afterCreating(function (StoredFile $file): void {
            if ($file->status === FileStatus::Available) {
                Storage::disk('vault')->put($file->storage_key, self::CONTENTS);
            }

            if ($file->quota_state === QuotaState::Released) {
                return;
            }

            DB::transaction(function () use ($file): void {
                $account = StorageAccount::query()->whereKey($file->owner_id)->lockForUpdate()->firstOrFail();
                $counter = $file->quota_state === QuotaState::Used ? 'used_bytes' : 'reserved_bytes';
                $account->increment($counter, $file->size_bytes);
            });
        });
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FileStatus::Available,
            'quota_state' => QuotaState::Used,
            'size_bytes' => strlen(self::CONTENTS),
            'sha256' => hash('sha256', self::CONTENTS),
        ]);
    }

    public function deleting(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FileStatus::Deleting,
            'delete_requested_at' => now(),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FileStatus::Deleted,
            'quota_state' => QuotaState::Released,
            'delete_requested_at' => now(),
            'deleted_at' => now(),
        ]);
    }
}
