<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

final class LoginRequest extends FortifyLoginRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50', 'regex:/\A[a-z0-9._-]+\z/'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'username.max' => __('auth.failed'),
            'username.regex' => __('auth.failed'),
        ];
    }
}
