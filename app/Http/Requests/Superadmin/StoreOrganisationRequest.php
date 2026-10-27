<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin;

use App\Data\Organisations\CreateOrganisationData;
use App\Models\Organisation;
use Illuminate\Foundation\Http\FormRequest;

final class StoreOrganisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organisation::class) ?? false;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'public_id' => ['missing'], 'id' => ['missing']];
    }

    public function toData(): CreateOrganisationData
    {
        return new CreateOrganisationData($this->validated('name'));
    }
}
