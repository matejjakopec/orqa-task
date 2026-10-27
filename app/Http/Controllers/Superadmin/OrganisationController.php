<?php

declare(strict_types=1);

namespace App\Http\Controllers\Superadmin;

use App\Contracts\Organisations\OrganisationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\StoreOrganisationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class OrganisationController extends Controller
{
    public function __construct(private OrganisationServiceInterface $organisations) {}

    public function index(Request $request): View
    {
        $validated = $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        return view('organisations.index', ['organisations' => $this->organisations->paginate($request->user(), (int) ($validated['page'] ?? 1))]);
    }

    public function create(): View
    {
        return view('organisations.create');
    }

    public function store(StoreOrganisationRequest $request): RedirectResponse
    {
        $this->organisations->create($request->user(), $request->toData());

        return redirect()->route('superadmin.organisations.index')->with('status', __('accounts.organisation_created'));
    }
}
