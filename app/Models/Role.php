<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
final class Role extends Model
{
    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['name' => RoleName::class];
    }
}
