<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Data\Users\CreateUserData;
use App\Enums\RoleName;
use App\Models\User;
use App\Support\AccountIdentifier;
use App\Support\AccountValidationRules;
use Illuminate\Foundation\Http\FormRequest;

abstract class StoreAccountRequest extends FormRequest
{
    use AccountValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['username', 'email'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => AccountIdentifier::normalize($this->input($field))]);
            }
        }

        if (is_string($this->input('country'))) {
            $this->merge(['country' => strtoupper(trim($this->input('country')))]);
        }
    }

    /** @param list<RoleName> $roles */
    protected function accountData(?int $organisationId, array $roles): CreateUserData
    {
        $data = $this->validated();

        return new CreateUserData(
            username: $data['username'], email: $data['email'],
            firstName: $data['first_name'], lastName: $data['last_name'],
            organisationId: $organisationId, roles: $roles,
            address: $data['address'] ?? null, city: $data['city'] ?? null,
            postalCode: $data['postal_code'] ?? null, country: $data['country'] ?? null,
        );
    }
}
