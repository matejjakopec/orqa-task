<?php

declare(strict_types=1);

namespace App\Data\Users;

use App\Enums\RoleName;
use InvalidArgumentException;

final readonly class UserListCriteria
{
    public ?string $search;

    public function __construct(
        public ?int $organisationId = null,
        public ?RoleName $role = null,
        ?string $search = null,
        public int $page = 1,
    ) {
        if ($page < 1 || ($organisationId !== null && $organisationId < 1)) {
            throw new InvalidArgumentException('Pages and organisation identifiers must be positive.');
        }

        $search = trim($search ?? '');

        if (mb_strlen($search) > 100) {
            throw new InvalidArgumentException('Search terms must not exceed 100 characters.');
        }

        $this->search = $search === '' ? null : $search;
    }
}
