<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuditEvent> */
final class AuditEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->admin(),
            'organisation_id' => fn (array $attributes): ?int => User::query()->findOrFail($attributes['actor_id'])->organisation_id,
            'event_name' => 'account.created',
            'subject_type' => 'user',
            'subject_public_id' => fn (array $attributes): string => User::query()->findOrFail($attributes['actor_id'])->public_id,
            'correlation_id' => (string) Str::uuid(),
            'context' => [],
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes): array => [
            'actor_id' => null,
            'organisation_id' => null,
            'event_name' => 'storage.reconciled',
            'subject_type' => null,
            'subject_public_id' => null,
        ]);
    }
}
