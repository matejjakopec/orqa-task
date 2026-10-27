<?php

declare(strict_types=1);

namespace App\Contracts\Users;

use App\Data\Users\CreateUserData;
use App\Data\Users\UpdateProfileData;
use App\Data\Users\UserListCriteria;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /** @return LengthAwarePaginator<int, User> */
    public function paginateVisibleTo(User $actor, UserListCriteria $criteria): LengthAwarePaginator;

    public function findVisibleTo(User $actor, string $publicId): ?User;

    public function findOwnProfile(User $actor): User;

    public function lockActor(User $actor): User;

    public function create(CreateUserData $data, string $passwordHash, int $quotaBytes): User;

    public function updateProfile(User $user, UpdateProfileData $data): User;

    /** @return Collection<int, User> */
    public function pendingSetupLinks(CarbonInterface $createdBefore, int $limit, int $afterId): Collection;
}
