<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Data\Users\UpdateProfileData;
use App\Support\AccountValidationRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
{
    use AccountValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->user()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('country'))) {
            $this->merge(['country' => strtoupper(trim($this->input('country')))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->profileRules() + $this->protectedAccountRules() + array_fill_keys([
            'username', 'email', 'organisation_id', 'role_set',
        ], ['missing']);
    }

    public function toData(): UpdateProfileData
    {
        $data = $this->validated();

        return new UpdateProfileData(
            firstName: $data['first_name'], lastName: $data['last_name'],
            address: $data['address'] ?? null, city: $data['city'] ?? null,
            postalCode: $data['postal_code'] ?? null, country: $data['country'] ?? null,
        );
    }
}
