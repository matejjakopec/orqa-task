<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Users\UserAdministrationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Users\ListUsersRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class UserController extends Controller
{
    public function __construct(private UserAdministrationServiceInterface $users) {}

    public function index(ListUsersRequest $request): View
    {
        return view('users.index', [
            'users' => $this->users->paginate($request->user(), $request->toCriteria()),
            'area' => 'admin', 'organisations' => collect(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        return view('users.create', ['area' => 'admin', 'organisations' => collect(), 'organisation' => $request->user()->organisation]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->user(), $request->toData());

        return redirect()->route('admin.users.show', $user->public_id)->with('status', __('accounts.created'));
    }

    public function show(Request $request, string $publicId): View
    {
        return view('users.show', ['user' => $this->users->find($request->user(), $publicId), 'area' => 'admin']);
    }
}
