<?php

declare(strict_types=1);

namespace App\Contracts\Organisations;

use App\Data\Organisations\CreateOrganisationData;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrganisationServiceInterface
{
    /** @return LengthAwarePaginator<int, Organisation> */
    public function paginate(User $actor, int $page): LengthAwarePaginator;

    /** @return Collection<int, Organisation> */
    public function options(User $actor): Collection;

    public function create(User $actor, CreateOrganisationData $data): Organisation;
}
