<?php

declare(strict_types=1);

namespace App\Contracts\Organisations;

use App\Data\Organisations\CreateOrganisationData;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrganisationRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Organisation> */
    public function paginateVisibleTo(User $actor, int $page = 1): LengthAwarePaginator;

    public function findVisibleTo(User $actor, string $publicId): ?Organisation;

    public function findForProvisioning(User $actor, int $id): ?Organisation;

    /** @return Collection<int, Organisation> */
    public function optionsVisibleTo(User $actor): Collection;

    public function create(CreateOrganisationData $data): Organisation;
}
