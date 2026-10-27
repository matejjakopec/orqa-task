<?php

declare(strict_types=1);

namespace App\Services\Organisations;

use App\Contracts\Audit\AuditWriterInterface;
use App\Contracts\Organisations\OrganisationRepositoryInterface;
use App\Contracts\Organisations\OrganisationServiceInterface;
use App\Contracts\Users\UserRepositoryInterface;
use App\Data\Organisations\CreateOrganisationData;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class OrganisationService implements OrganisationServiceInterface
{
    public function __construct(private OrganisationRepositoryInterface $organisations, private UserRepositoryInterface $users, private AuditWriterInterface $audit) {}

    /** @return LengthAwarePaginator<int, Organisation> */
    public function paginate(User $actor, int $page): LengthAwarePaginator
    {
        Gate::forUser($actor)->authorize('viewAny', Organisation::class);

        return $this->organisations->paginateVisibleTo($actor, $page);
    }

    /** @return Collection<int, Organisation> */
    public function options(User $actor): Collection
    {
        Gate::forUser($actor)->authorize('viewAny', Organisation::class);

        return $this->organisations->optionsVisibleTo($actor);
    }

    public function create(User $actor, CreateOrganisationData $data): Organisation
    {
        Gate::forUser($actor)->authorize('create', Organisation::class);

        return DB::transaction(function () use ($actor, $data): Organisation {
            $currentActor = $this->users->lockActor($actor);
            Gate::forUser($currentActor)->authorize('create', Organisation::class);
            $organisation = $this->organisations->create($data);
            $this->audit->write($currentActor, 'organisation.created', 'organisation', $organisation->public_id, $organisation->id);

            return $organisation;
        });
    }
}
