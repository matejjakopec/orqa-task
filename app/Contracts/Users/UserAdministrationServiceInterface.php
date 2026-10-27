<?php

declare(strict_types=1);

namespace App\Contracts\Users;

use App\Data\Users\CreateUserData;
use App\Data\Users\UserListCriteria;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserAdministrationServiceInterface
{
    /** @return LengthAwarePaginator<int, User> */
    public function paginate(User $actor, UserListCriteria $criteria): LengthAwarePaginator;

    public function find(User $actor, string $publicId): User;

    public function create(User $actor, CreateUserData $data): User;
}
