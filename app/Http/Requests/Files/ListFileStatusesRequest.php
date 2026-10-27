<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use App\Models\StoredFile;
use Illuminate\Foundation\Http\FormRequest;

final class ListFileStatusesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StoredFile::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['ids' => ['required', 'array', 'list', 'min:1', 'max:5'], 'ids.*' => ['required', 'uuid', 'distinct:strict']];
    }

    /** @return list<string> */
    public function publicIds(): array
    {
        return $this->validated('ids');
    }
}
