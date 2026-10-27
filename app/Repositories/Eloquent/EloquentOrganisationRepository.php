<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Contracts\Organisations\OrganisationRepositoryInterface;
use App\Data\Organisations\CreateOrganisationData;
use App\Enums\RoleName;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class EloquentOrganisationRepository implements OrganisationRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Organisation> */
    public function paginateVisibleTo(User $actor, int $page = 1): LengthAwarePaginator
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Pages must be positive.');
        }

        return $this->visibleQuery($actor)->orderByDesc('created_at')->orderByDesc('id')->paginate(15, ['*'], 'page', $page);
    }

    public function findVisibleTo(User $actor, string $publicId): ?Organisation
    {
        if (! Str::isUuid($publicId)) {
            return null;
        }

        return $this->visibleQuery($actor)->where('public_id', $publicId)->first();
    }

    public function findForProvisioning(User $actor, int $id): ?Organisation
    {
        if (! Gate::forUser($actor)->allows('viewAny', User::class)) {
            return null;
        }

        $query = Organisation::query()->whereKey($id);

        if (! $actor->hasRole(RoleName::Superadmin)) {
            $query->where('id', $actor->organisation_id);
        }

        return $query->first();
    }

    /** @return Collection<int, Organisation> */
    public function optionsVisibleTo(User $actor): Collection
    {
        return $this->visibleQuery($actor)->orderBy('name')->orderBy('id')->get();
    }

    public function create(CreateOrganisationData $data): Organisation
    {
        return Organisation::query()->create(['name' => $data->name]);
    }

    /** @return Builder<Organisation> */
    private function visibleQuery(User $actor): Builder
    {
        $query = Organisation::query();

        if (! Gate::forUser($actor)->allows('viewAny', Organisation::class)) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
