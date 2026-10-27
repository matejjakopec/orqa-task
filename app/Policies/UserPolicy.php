<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Organisation;
use App\Models\User;

final class UserPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        return $actor->exists && $actor->isActive() ? null : false;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->hasRole(RoleName::Superadmin)
            || ($actor->hasRole(RoleName::Admin) && $actor->organisation_id !== null);
    }

    public function view(User $actor, User $target): bool
    {
        if (! $target->exists) {
            return false;
        }

        if ($actor->hasRole(RoleName::Superadmin)) {
            return true;
        }

        $target->loadMissing('roles');

        return $actor->hasRole(RoleName::Admin)
            && $actor->organisation_id !== null
            && $actor->organisation_id === $target->organisation_id
            && $target->hasRole(RoleName::User)
            && ! $target->hasRole(RoleName::Admin)
            && ! $target->hasRole(RoleName::Superadmin);
    }

    /** @param list<mixed> $roles */
    public function create(User $actor, Organisation $organisation, array $roles): bool
    {
        if (! $organisation->exists || $roles === []) {
            return false;
        }

        $names = [];

        foreach ($roles as $role) {
            if (! $role instanceof RoleName || ! in_array($role, [RoleName::User, RoleName::Admin], true)) {
                return false;
            }

            $names[] = $role->value;
        }

        if (count($names) !== count(array_unique($names))) {
            return false;
        }

        if ($actor->hasRole(RoleName::Superadmin)) {
            return true;
        }

        return $actor->hasRole(RoleName::Admin)
            && $actor->organisation_id !== null
            && $actor->organisation_id === $organisation->id
            && $roles === [RoleName::User];
    }

    public function update(User $actor, User $target): bool
    {
        return $target->exists && $actor->exists && $actor->id === $target->id;
    }

    public function delete(User $actor, User $target): bool
    {
        return false;
    }
}
