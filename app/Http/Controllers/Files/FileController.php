<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Contracts\Files\FileServiceInterface;
use App\Contracts\Organisations\OrganisationServiceInterface;
use App\Enums\FileStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Files\ListFilesRequest;
use App\Http\Requests\Files\UploadFileRequest;
use App\Models\StoredFile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class FileController extends Controller
{
    public function __construct(private FileServiceInterface $files) {}

    public function index(ListFilesRequest $request, OrganisationServiceInterface $organisations): View
    {
        $actor = $request->user();
        $global = $actor->hasRole(RoleName::Superadmin);

        return view('files.index', [
            'files' => $this->files->paginate($actor, $request->toCriteria()),
            'account' => $this->files->storageSummary($actor), 'global' => $global,
            'canUpload' => Gate::forUser($actor)->allows('create', StoredFile::class),
            'organisations' => $global ? $organisations->options($actor) : collect(),
            'filters' => $request->safe()->only(['search', 'owner_id', 'organisation_id']),
        ]);
    }

    public function store(UploadFileRequest $request): RedirectResponse
    {
        $file = $this->files->upload($request->user(), $request->toData());

        $message = match ($file->status) {
            FileStatus::Available => 'files.uploaded',
            FileStatus::Quarantined, FileStatus::Scanning => 'files.uploaded_pending',
            default => 'files.upload_cancelled',
        };

        return redirect()->route('files.index')->with('status', __($message));
    }

    public function show(Request $request, string $publicId): View
    {
        return view('files.delete', ['file' => $this->files->findForDeletion($request->user(), $publicId)]);
    }

    public function destroy(Request $request, string $publicId): RedirectResponse
    {
        $this->files->requestDeletion($request->user(), $publicId);

        return redirect()->route('files.index')->with('status', __('files.deletion_requested'));
    }
}
