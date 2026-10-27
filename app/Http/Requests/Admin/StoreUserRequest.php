<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Data\Users\CreateUserData;
use App\Enums\RoleName;
use App\Http\Requests\Users\StoreAccountRequest;

final class StoreUserRequest extends StoreAccountRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->identityRules() + $this->profileRules() + $this->protectedAccountRules() + [
            'organisation_id' => ['missing'],
            'role_set' => ['missing'],
        ];
    }

    public function toData(): CreateUserData
    {
        return $this->accountData($this->user()->organisation_id, [RoleName::User]);
    }
}
