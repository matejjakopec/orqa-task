<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Contracts\Organisations\OrganisationServiceInterface;
use App\Contracts\Users\UserAdministrationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\StoreUserRequest;
use App\Http\Requests\Users\ListUsersRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class UserController extends Controller
{
    public function __construct(private UserAdministrationServiceInterface $users, private OrganisationServiceInterface $organisations) {}

    public function index(ListUsersRequest $request): View
    {
        return view('users.index', [
            'users' => $this->users->paginate($request->user(), $request->toCriteria()),
            'area' => 'superadmin', 'organisations' => $this->organisations->options($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        return view('users.create', ['area' => 'superadmin', 'organisations' => $this->organisations->options($request->user()), 'organisation' => null]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->user(), $request->toData());

        return redirect()->route('superadmin.users.show', $user->public_id)->with('status', __('accounts.created'));
    }

    public function show(Request $request, string $publicId): View
    {
        return view('users.show', ['user' => $this->users->find($request->user(), $publicId), 'area' => 'superadmin']);
    }
}
