<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\FileStatus;
use App\Enums\QuotaState;
use App\Enums\RoleName;
use App\Models\StoredFile;
use App\Models\User;

final class StoredFilePolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        return $actor->exists && $actor->isActive() ? null : false;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->hasRole(RoleName::Superadmin)
            || ($actor->hasRole(RoleName::User) && $actor->organisation_id !== null);
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole(RoleName::User) && $actor->organisation_id !== null;
    }

    public function view(User $actor, StoredFile $file): bool
    {
        return $this->canManage($actor, $file)
            && $file->status !== FileStatus::Deleted
            && $file->deleted_at === null;
    }

    public function download(User $actor, StoredFile $file): bool
    {
        return $this->view($actor, $file)
            && $file->status === FileStatus::Available
            && $file->quota_state === QuotaState::Used
            && $file->delete_requested_at === null
            && $file->hasSuccessfulScan();
    }

    public function delete(User $actor, StoredFile $file): bool
    {
        return $this->canManage($actor, $file);
    }

    public function update(User $actor, StoredFile $file): bool
    {
        return false;
    }

    public function restore(User $actor, StoredFile $file): bool
    {
        return false;
    }

    public function forceDelete(User $actor, StoredFile $file): bool
    {
        return false;
    }

    private function canManage(User $actor, StoredFile $file): bool
    {
        return $file->exists && ($actor->hasRole(RoleName::Superadmin)
            || ($actor->hasRole(RoleName::User)
                && $actor->organisation_id !== null
                && $file->owner_id === $actor->id
                && $file->organisation_id === $actor->organisation_id));
    }
}
