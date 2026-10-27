<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\Rule;

trait AccountValidationRules
{
    /** @return array<string, mixed> */
    protected function identityRules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', 'regex:/\A[a-z0-9._-]+\z/', Rule::unique('users', 'username')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
        ];
    }

    /** @return array<string, list<string>> */
    protected function profileRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2', 'regex:/\A[A-Z]{2}\z/'],
        ];
    }

    /** @return array<string, list<string>> */
    protected function protectedAccountRules(): array
    {
        return array_fill_keys([
            'id', 'public_id', 'user_id', 'owner_id', 'role', 'role_id', 'roles',
            'quota_bytes', 'used_bytes', 'reserved_bytes', 'storage_account',
            'password', 'activated_at', 'disabled_at', 'email_verified_at',
            'remember_token', 'setup_link_sent_at',
        ], ['missing']);
    }
}
