<?php

declare(strict_types=1);

namespace App\Data\Files;

use InvalidArgumentException;

final readonly class FileListCriteria
{
    public ?string $search;

    public function __construct(
        public ?int $ownerId = null,
        public ?int $organisationId = null,
        ?string $search = null,
        public int $page = 1,
    ) {
        if ($page < 1 || ($ownerId !== null && $ownerId < 1) || ($organisationId !== null && $organisationId < 1)) {
            throw new InvalidArgumentException('Pages and filter identifiers must be positive.');
        }

        $search = trim($search ?? '');

        if (mb_strlen($search) > 100) {
            throw new InvalidArgumentException('Search terms must not exceed 100 characters.');
        }

        $this->search = $search === '' ? null : $search;
    }
}
