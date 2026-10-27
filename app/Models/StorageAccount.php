<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StorageAccount extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'quota_bytes' => 'integer',
            'used_bytes' => 'integer',
            'reserved_bytes' => 'integer',
        ];
    }
}
