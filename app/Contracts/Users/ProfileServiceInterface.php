<?php

declare(strict_types=1);

namespace App\Contracts\Users;

use App\Data\Users\UpdateProfileData;
use App\Models\User;

interface ProfileServiceInterface
{
    public function get(User $actor): User;

    public function update(User $actor, UpdateProfileData $data): User;
}
