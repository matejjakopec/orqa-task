<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Contracts\Audit\AuditWriterInterface;
use App\Contracts\Organisations\OrganisationRepositoryInterface;
use App\Contracts\Users\UserAdministrationServiceInterface;
use App\Contracts\Users\UserRepositoryInterface;
use App\Data\Users\CreateUserData;
use App\Data\Users\UserListCriteria;
use App\Enums\RoleName;
use App\Jobs\SendAccountSetupLink;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Throwable;

final class UserAdministrationService implements UserAdministrationServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OrganisationRepositoryInterface $organisations,
        private AuditWriterInterface $audit,
    ) {}

    /** @return LengthAwarePaginator<int, User> */
    public function paginate(User $actor, UserListCriteria $criteria): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', User::class);

        return $this->users->paginateVisibleTo($actor, $criteria);
    }

    public function find(User $actor, string $publicId): User
    {
        $user = $this->users->findVisibleTo($actor, $publicId)
            ?? throw (new ModelNotFoundException)->setModel(User::class, [$publicId]);
        Gate::forUser($actor)->authorize('view', $user);

        return $user;
    }

    public function create(User $actor, CreateUserData $data): User
    {
        Gate::forUser($actor)->authorize('viewAny', User::class);
        $quota = config('files.default_quota_bytes');

        if (! is_int($quota) || $quota < 0) {
            throw new LogicException('The default storage quota must be a non-negative integer.');
        }

        $passwordHash = Hash::make(Str::random(64));

        try {
            return DB::transaction(function () use ($actor, $data, $quota, $passwordHash): User {
                $currentActor = $this->users->lockActor($actor);
                $organisation = $data->organisationId === null ? null : $this->organisations->findForProvisioning($currentActor, $data->organisationId);

                if ($organisation === null) {
                    throw new AuthorizationException;
                }

                Gate::forUser($currentActor)->authorize('create', [User::class, $organisation, $data->roles]);
                $user = $this->users->create($data, $passwordHash, $quota);
                $this->audit->write($currentActor, 'account.created', 'user', $user->public_id, $user->organisation_id, [
                    'roles' => array_map(fn (RoleName $role): string => $role->value, $data->roles),
                ]);

                DB::afterCommit(function () use ($user): void {
                    try {
                        SendAccountSetupLink::dispatch($user->id);
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                });

                return $user;
            });
        } catch (UniqueConstraintViolationException $exception) {
            foreach (['username', 'email'] as $field) {
                if (str_contains($exception->getMessage(), 'users_'.$field.'_unique')) {
                    throw ValidationException::withMessages([$field => __('validation.unique', ['attribute' => $field])]);
                }
            }

            throw $exception;
        }
    }
}
