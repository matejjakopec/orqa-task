<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Contracts\Users\ProfileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    public function __construct(private ProfileServiceInterface $profiles) {}

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $this->profiles->get($request->user())]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->profiles->update($request->user(), $request->toData());

        return redirect()->route('profile.edit')->with('status', __('accounts.profile_updated'));
    }
}
