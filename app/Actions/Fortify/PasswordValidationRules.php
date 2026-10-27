<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Closure;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /** @return list<string|Password|Closure> */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(12),
            'confirmed',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && strlen($value) > 72) {
                    $fail(__('auth.password_too_long'));
                }
            },
        ];
    }
}
