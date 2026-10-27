<?php

declare(strict_types=1);

namespace App\Data\Organisations;

final readonly class CreateOrganisationData
{
    public function __construct(public string $name) {}
}
