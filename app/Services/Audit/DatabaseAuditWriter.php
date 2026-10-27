<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Contracts\Audit\AuditWriterInterface;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Str;

final class DatabaseAuditWriter implements AuditWriterInterface
{
    /** @param array<string, mixed> $context */
    public function write(?User $actor, string $event, string $subjectType, string $subjectPublicId, ?int $organisationId, array $context = []): void
    {
        $safeContext = [];

        foreach (['roles' => ['user', 'admin', 'superadmin'], 'fields' => ['first_name', 'last_name', 'address', 'city', 'postal_code', 'country']] as $key => $allowed) {
            if (isset($context[$key]) && is_array($context[$key])) {
                $safeContext[$key] = array_values(array_filter($context[$key], fn (mixed $value): bool => is_string($value) && in_array($value, $allowed, true)));
            }
        }

        foreach (['storage_before', 'storage_after'] as $key) {
            if (! isset($context[$key]) || ! is_array($context[$key])) {
                continue;
            }

            $safeContext[$key] = array_filter(
                array_intersect_key($context[$key], array_flip(['used_bytes', 'reserved_bytes'])),
                fn (mixed $value): bool => is_int($value) && $value >= 0,
            );
        }

        if (isset($context['reason']) && in_array($context['reason'], ['object_missing', 'integrity_mismatch', 'charged_tombstone', 'uncharged_live_file', 'available_reserved', 'scan_required', 'deletion_incomplete'], true)) {
            $safeContext['reason'] = $context['reason'];
        }

        $audit = new AuditEvent;
        $audit->forceFill([
            'actor_id' => $actor?->id, 'organisation_id' => $organisationId,
            'event_name' => $event, 'subject_type' => $subjectType,
            'subject_public_id' => $subjectPublicId, 'correlation_id' => (string) Str::uuid(),
            'context' => $safeContext,
        ])->save();
    }
}
