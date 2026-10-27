<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin;

use App\Data\Users\CreateUserData;
use App\Enums\RoleName;
use App\Http\Requests\Users\StoreAccountRequest;
use Illuminate\Validation\Rule;
use LogicException;

final class StoreUserRequest extends StoreAccountRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->identityRules() + $this->profileRules() + $this->protectedAccountRules() + [
            'organisation_id' => ['required', 'integer', Rule::exists('organisations', 'id')],
            'role_set' => ['required', Rule::in(['user', 'admin', 'admin_user'])],
        ];
    }

    public function toData(): CreateUserData
    {
        $roles = match ($this->validated('role_set')) {
            'user' => [RoleName::User],
            'admin' => [RoleName::Admin],
            'admin_user' => [RoleName::Admin, RoleName::User],
            default => throw new LogicException('The validated account role selector is unsupported.'),
        };

        return $this->accountData((int) $this->validated('organisation_id'), $roles);
    }
}
