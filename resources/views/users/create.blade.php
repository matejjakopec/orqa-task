<x-layouts.app :title="__('accounts.create_user')">
    <x-page-header :title="__('accounts.create_user')"><x-slot:actions><x-button :href="route($area.'.users.index')" variant="outline-secondary">{{ __('accounts.back_users') }}</x-button></x-slot:actions></x-page-header>
    @if($area === 'superadmin' && $organisations->isEmpty())
        <x-card><x-empty-state :title="__('accounts.empty_organisations')"><x-slot:action><x-button :href="route('superadmin.organisations.create')">{{ __('accounts.create_organisation') }}</x-button></x-slot:action></x-empty-state></x-card>
    @else
        <div class="d-grid gap-3"><div>
            <x-card>
                <x-form :action="route($area.'.users.store')">
                    @if($area === 'superadmin')
                        <div class="d-grid gap-3">
                            <div><x-form-select name="organisation_id" :label="__('ui.organisation')" :options="$organisations->mapWithKeys(fn (\App\Models\Organisation $item): array => [$item->id => $item->name.' · '.substr($item->public_id, -8)])->all()" required /></div>
                            <div><x-form-select name="role_set" :label="__('accounts.role_set')" :options="['user' => __('accounts.access_user'), 'admin' => __('accounts.access_admin'), 'admin_user' => __('accounts.access_admin_user')]" value="user" required /></div>
                        </div>
                    @else
                        <x-alert variant="info">{{ __('accounts.local_scope', ['organisation' => $organisation->name]) }}</x-alert>
                    @endif
                    <div class="d-grid gap-3">
                        <div><x-form-input name="username" :label="__('ui.username')" autocomplete="off" autocapitalize="none" maxlength="50" required /></div>
                        <div><x-form-input name="email" type="email" :label="__('ui.email')" autocomplete="off" maxlength="255" required /></div>
                    </div>
                    <x-profile-fields />
                    <div class="form-actions d-grid gap-2"><x-button type="submit">{{ __('accounts.create_user') }}</x-button><x-button :href="route($area.'.users.index')" variant="outline-secondary">{{ __('ui.cancel') }}</x-button></div>
                </x-form>
            </x-card>
        </div></div>
    @endif
</x-layouts.app>
