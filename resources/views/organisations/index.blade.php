<x-layouts.app :title="__('accounts.organisations')">
    <x-page-header :title="__('accounts.organisations')"><x-slot:actions><x-button :href="route('superadmin.organisations.create')">{{ __('accounts.create_organisation') }}</x-button></x-slot:actions></x-page-header>
    <x-card>
        @if($organisations->count())
            <div class="table-responsive"><table class="table align-middle account-table mb-0">
                <thead><tr><th scope="col">{{ __('accounts.organisation_name') }}</th><th scope="col">{{ __('accounts.reference') }}</th><th scope="col">{{ __('accounts.created_at') }}</th></tr></thead>
                <tbody>@foreach($organisations as $organisation)<tr><td class="text-break">{{ $organisation->name }}</td><td class="small text-break">{{ $organisation->public_id }}</td><td>{{ $organisation->created_at->format('Y-m-d') }}</td></tr>@endforeach</tbody>
            </table></div>
            <div class="mt-4">{{ $organisations->links() }}</div>
        @else
            <x-empty-state :title="__('accounts.empty_organisations')"><x-slot:action><x-button :href="route('superadmin.organisations.create')">{{ __('accounts.create_organisation') }}</x-button></x-slot:action></x-empty-state>
        @endif
    </x-card>
</x-layouts.app>
