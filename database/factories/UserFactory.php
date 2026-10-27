<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\StorageAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::factory(),
            'username' => fake()->unique()->bothify('????????.########'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->countryCode(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'activated_at' => now(),
            'disabled_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function pendingActivation(): static
    {
        return $this->state(fn (array $attributes): array => ['activated_at' => null]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => ['disabled_at' => now()]);
    }

    public function ordinaryUser(): static
    {
        return $this->withRoles(RoleName::User);
    }

    public function admin(): static
    {
        return $this->withRoles(RoleName::Admin);
    }

    public function adminAndUser(): static
    {
        return $this->withRoles(RoleName::Admin, RoleName::User);
    }

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes): array => ['organisation_id' => null])
            ->withRoles(RoleName::Superadmin);
    }

    public function withRoles(RoleName ...$names): static
    {
        $requiresOrganisation = in_array(RoleName::User, $names, true) || in_array(RoleName::Admin, $names, true);

        return $this->afterMaking(function (User $user) use ($requiresOrganisation): void {
            if ($requiresOrganisation && $user->organisation_id === null) {
                throw new \InvalidArgumentException('User and admin factory states require an organisation.');
            }
        })->afterCreating(function (User $user) use ($names): void {
            foreach ($names as $name) {
                $role = Role::query()->firstOrCreate(['name' => $name->value]);
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            if (in_array(RoleName::User, $names, true) && ! $user->storageAccount()->exists()) {
                $account = new StorageAccount;
                $account->user()->associate($user);
                $account->quota_bytes = config('files.default_quota_bytes');
                $account->save();
            }
        });
    }
}
