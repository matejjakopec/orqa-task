<x-layouts.app :title="__($area === 'superadmin' ? 'accounts.all_users' : 'accounts.local_users')">
    <x-page-header :title="__($area === 'superadmin' ? 'accounts.all_users' : 'accounts.local_users')">
        <x-slot:actions><x-button :href="route($area.'.users.create')">{{ __('accounts.create_user') }}</x-button></x-slot:actions>
    </x-page-header>
    <div class="mb-4">
        <x-card>
            <x-form :action="route($area.'.users.index')" method="GET">
                <div class="d-grid gap-3">
                    <div><x-form-input name="search" :label="__('accounts.search')" :value="request('search')" :placeholder="__('accounts.search_hint')" maxlength="100" /></div>
                    @if($area === 'superadmin')
                        <div><x-form-select name="organisation_id" :label="__('ui.organisation')" :options="$organisations->mapWithKeys(fn (\App\Models\Organisation $organisation): array => [$organisation->id => $organisation->name.' · '.substr($organisation->public_id, -8)])->all()" :value="request('organisation_id')" :placeholder="__('accounts.all_organisations')" /></div>
                        <div><x-form-select name="role" :label="__('ui.roles')" :options="['user' => __('ui.role_user'), 'admin' => __('ui.role_admin'), 'superadmin' => __('ui.role_superadmin')]" :value="request('role')" :placeholder="__('accounts.all_roles')" /></div>
                    @endif
                    <div class="d-grid"><x-button type="submit">{{ __('accounts.filter') }}</x-button></div>
                </div>
            </x-form>
        </x-card>
    </div>
    <x-card>
        @if($users->count())
            <x-users.table :users="$users" :area="$area" />
            <div class="mt-4">{{ $users->appends(request()->only(['search', 'organisation_id', 'role']))->links() }}</div>
        @elseif(request()->filled('search') || request()->filled('organisation_id') || request()->filled('role'))
            <x-empty-state :title="__('accounts.no_matches')"><x-slot:action><x-button :href="route($area.'.users.index')" variant="outline-secondary">{{ __('accounts.clear_filters') }}</x-button></x-slot:action></x-empty-state>
        @else
            <x-empty-state :title="__('accounts.empty_users')"><x-slot:action><x-button :href="route($area.'.users.create')">{{ __('accounts.create_user') }}</x-button></x-slot:action></x-empty-state>
        @endif
    </x-card>
</x-layouts.app>
