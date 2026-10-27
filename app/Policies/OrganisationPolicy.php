<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Organisation;
use App\Models\User;

final class OrganisationPolicy
{
    public function before(User $actor, string $ability): ?bool
    {
        return $actor->exists && $actor->isActive() ? null : false;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->hasRole(RoleName::Superadmin);
    }

    public function view(User $actor, Organisation $organisation): bool
    {
        return $organisation->exists && $actor->hasRole(RoleName::Superadmin);
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole(RoleName::Superadmin);
    }

    public function update(User $actor, Organisation $organisation): bool
    {
        return false;
    }

    public function delete(User $actor, Organisation $organisation): bool
    {
        return false;
    }
}
