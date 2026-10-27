<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Data\Users\UserListCriteria;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $global = $this->routeIs('superadmin.*');

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'organisation_id' => $global ? ['nullable', 'integer', Rule::exists('organisations', 'id')] : ['missing'],
            'role' => $global ? ['nullable', Rule::enum(RoleName::class)] : ['missing'],
        ];
    }

    public function toCriteria(): UserListCriteria
    {
        $data = $this->validated();

        return new UserListCriteria(
            organisationId: isset($data['organisation_id']) ? (int) $data['organisation_id'] : null,
            role: isset($data['role']) ? RoleName::from($data['role']) : null,
            search: $data['search'] ?? null,
            page: (int) ($data['page'] ?? 1),
        );
    }
}
