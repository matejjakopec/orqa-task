<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Users\UserRepositoryInterface;
use App\Data\Users\CreateUserData;
use App\Data\Users\UpdateProfileData;
use App\Data\Users\UserListCriteria;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\StorageAccount;
use App\Models\User;
use App\Support\AccountIdentifier;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class EloquentUserRepository implements UserRepositoryInterface
{
    /** @return LengthAwarePaginator<int, User> */
    public function paginateVisibleTo(User $actor, UserListCriteria $criteria): LengthAwarePaginator
    {
        $query = $this->visibleQuery($actor)->with(['organisation', 'roles', 'storageAccount']);

        if ($criteria->organisationId !== null) {
            $query->where('organisation_id', $criteria->organisationId);
        }

        if ($criteria->role !== null) {
            $query->whereHas('roles', fn (Builder $roles): Builder => $roles->where('name', $criteria->role->value));
        }

        if ($criteria->search !== null) {
            $search = '%'.$criteria->search.'%';
            $query->where(function (Builder $users) use ($criteria, $search): void {
                $users->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('email', 'like', $search);

                if (mb_check_encoding($criteria->search, 'ASCII')) {
                    $users->orWhere('username', 'like', '%'.AccountIdentifier::normalize($criteria->search).'%');
                }
            });
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate(15, ['*'], 'page', $criteria->page);
    }

    public function findVisibleTo(User $actor, string $publicId): ?User
    {
        if (! Str::isUuid($publicId)) {
            return null;
        }

        return $this->visibleQuery($actor)->with(['organisation', 'roles', 'storageAccount'])->where('public_id', $publicId)->first();
    }

    public function findOwnProfile(User $actor): User
    {
        return User::query()->with(['organisation', 'roles', 'storageAccount'])->findOrFail($actor->id);
    }

    public function lockActor(User $actor): User
    {
        return User::query()->lockForUpdate()->findOrFail($actor->id)->load('roles');
    }

    public function create(CreateUserData $data, string $passwordHash, int $quotaBytes): User
    {
        $user = new User;
        $user->forceFill([
            'username' => $data->username, 'email' => $data->email,
            'first_name' => $data->firstName, 'last_name' => $data->lastName,
            'organisation_id' => $data->organisationId,
            'address' => $data->address, 'city' => $data->city,
            'postal_code' => $data->postalCode, 'country' => $data->country,
            'password' => $passwordHash, 'activated_at' => null,
        ])->save();

        foreach ($data->roles as $name) {
            $role = Role::query()->firstOrCreate(['name' => $name->value]);
            $user->roles()->attach($role->id);
        }

        if (in_array(RoleName::User, $data->roles, true)) {
            $account = new StorageAccount;
            $account->user()->associate($user);
            $account->quota_bytes = $quotaBytes;
            $account->used_bytes = 0;
            $account->reserved_bytes = 0;
            $account->save();
        }

        return $user->load(['organisation', 'roles', 'storageAccount']);
    }

    public function updateProfile(User $user, UpdateProfileData $data): User
    {
        $user->fill($data->attributes())->save();

        return $user;
    }

    /** @return Collection<int, User> */
    public function pendingSetupLinks(CarbonInterface $createdBefore, int $limit, int $afterId): Collection
    {
        return User::query()->whereNull('activated_at')->whereNull('disabled_at')
            ->whereNull('setup_link_sent_at')->where('created_at', '<=', $createdBefore)
            ->where('id', '>', $afterId)->orderBy('id')->limit($limit)->get();
    }

    /** @return Builder<User> */
    private function visibleQuery(User $actor): Builder
    {
        $query = User::query();
        $actor->loadMissing('roles');

        if (! Gate::forUser($actor)->allows('viewAny', User::class)) {
            return $query->whereRaw('1 = 0');
        }

        if (! $actor->hasRole(RoleName::Superadmin)) {
            $query->where('organisation_id', $actor->organisation_id)
                ->whereHas('roles', fn (Builder $roles): Builder => $roles->where('name', RoleName::User->value))
                ->whereDoesntHave('roles', fn (Builder $roles): Builder => $roles->whereIn('name', [RoleName::Admin->value, RoleName::Superadmin->value]));
        }

        return $query;
    }
}
