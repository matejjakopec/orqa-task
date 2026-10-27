<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use App\Data\Files\FileListCriteria;
use App\Enums\RoleName;
use App\Models\StoredFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListFilesRequest extends FormRequest
{
    protected $errorBag = 'fileFilters';

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StoredFile::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $global = $this->user()->hasRole(RoleName::Superadmin);

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'owner_id' => $global ? ['nullable', 'integer', Rule::exists('users', 'id')] : ['missing'],
            'organisation_id' => $global ? ['nullable', 'integer', Rule::exists('organisations', 'id')] : ['missing'],
            'per_page' => ['missing'],
        ];
    }

    public function toCriteria(): FileListCriteria
    {
        $data = $this->validated();

        return new FileListCriteria(
            ownerId: isset($data['owner_id']) ? (int) $data['owner_id'] : null,
            organisationId: isset($data['organisation_id']) ? (int) $data['organisation_id'] : null,
            search: $data['search'] ?? null, page: (int) ($data['page'] ?? 1),
        );
    }
}
