<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

final class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public function toResponse(mixed $request): Response
    {
        $message = __('auth.reset_link_sent');

        return $request->wantsJson()
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }
}
