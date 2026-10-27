<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleName;
use App\Support\AccountIdentifier;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'email', 'password', 'first_name', 'last_name', 'address', 'city', 'postal_code', 'country'])]
#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /** @return BelongsTo<Organisation, $this> */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /** @return HasOne<StorageAccount, $this> */
    public function storageAccount(): HasOne
    {
        return $this->hasOne(StorageAccount::class);
    }

    /** @return HasMany<StoredFile, $this> */
    public function storedFiles(): HasMany
    {
        return $this->hasMany(StoredFile::class, 'owner_id');
    }

    /** @return HasMany<AuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'actor_id');
    }

    public function hasRole(RoleName $name): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn (Role $role): bool => $role->name === $name);
        }

        return $this->roles()->where('name', $name->value)->exists();
    }

    public function isActive(): bool
    {
        return $this->activated_at !== null && $this->disabled_at === null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'organisation_id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activated_at' => 'datetime',
            'disabled_at' => 'datetime',
            'setup_link_sent_at' => 'datetime',
        ];
    }

    /** @return Attribute<string, never> */
    protected function displayName(): Attribute
    {
        return Attribute::make(get: fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    /** @return Attribute<string, string> */
    protected function username(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => AccountIdentifier::normalize($value));
    }

    /** @return Attribute<string, string> */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => AccountIdentifier::normalize($value));
    }
}
