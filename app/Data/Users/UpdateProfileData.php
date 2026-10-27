<?php

declare(strict_types=1);

namespace App\Data\Users;

final readonly class UpdateProfileData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $country = null,
    ) {}

    /** @return array{first_name: string, last_name: string, address: ?string, city: ?string, postal_code: ?string, country: ?string} */
    public function attributes(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
    }
}
