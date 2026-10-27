<?php

declare(strict_types=1);

namespace App\Contracts\Audit;

use App\Models\User;

interface AuditWriterInterface
{
    /** @param array<string, mixed> $context */
    public function write(?User $actor, string $event, string $subjectType, string $subjectPublicId, ?int $organisationId, array $context = []): void;
}
