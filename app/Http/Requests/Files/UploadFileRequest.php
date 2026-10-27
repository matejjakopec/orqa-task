<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use App\Data\Files\IncomingFile;
use App\Models\StoredFile;
use Illuminate\Foundation\Http\FormRequest;

final class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StoredFile::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['file' => ['required', 'file', 'max:'.intdiv(config('files.max_upload_bytes'), 1024)]]
            + array_fill_keys([
                'id', 'public_id', 'owner_id', 'user_id', 'organisation_id', 'storage_key',
                'original_name', 'extension', 'mime_type', 'size_bytes', 'sha256',
                'status', 'quota_state', 'quota_bytes', 'used_bytes', 'reserved_bytes',
                'lease_token', 'lease_expires_at', 'scanned_at', 'scanner_name',
                'delete_requested_at', 'deleted_at',
            ], ['missing']);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['file.max' => __('files.too_large')];
    }

    public function toData(): IncomingFile
    {
        return IncomingFile::fromUpload($this->validated('file'));
    }
}
