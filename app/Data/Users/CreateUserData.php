<?php

declare(strict_types=1);

namespace App\Data\Users;

use App\Enums\RoleName;
use App\Support\AccountIdentifier;

final readonly class CreateUserData
{
    public string $username;

    public string $email;

    /** @param list<RoleName> $roles */
    public function __construct(
        string $username,
        string $email,
        public string $firstName,
        public string $lastName,
        public ?int $organisationId,
        public array $roles,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $country = null,
    ) {
        $this->username = AccountIdentifier::normalize($username);
        $this->email = AccountIdentifier::normalize($email);
    }
}
