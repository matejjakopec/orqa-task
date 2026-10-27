<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Contracts\Audit\AuditWriterInterface;
use App\Contracts\Users\ProfileServiceInterface;
use App\Contracts\Users\UserRepositoryInterface;
use App\Data\Users\UpdateProfileData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ProfileService implements ProfileServiceInterface
{
    public function __construct(private UserRepositoryInterface $users, private AuditWriterInterface $audit) {}

    public function get(User $actor): User
    {
        Gate::forUser($actor)->authorize('update', $actor);
        $user = $this->users->findOwnProfile($actor);
        Gate::forUser($user)->authorize('update', $user);

        return $user;
    }

    public function update(User $actor, UpdateProfileData $data): User
    {
        Gate::forUser($actor)->authorize('update', $actor);

        return DB::transaction(function () use ($actor, $data): User {
            $user = $this->users->lockActor($actor);
            Gate::forUser($user)->authorize('update', $user);
            $user = $this->users->updateProfile($user, $data);
            $this->audit->write($user, 'profile.updated', 'user', $user->public_id, $user->organisation_id, ['fields' => array_keys($user->getChanges())]);

            return $user;
        });
    }
}
