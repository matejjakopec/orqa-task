<x-layouts.app :title="__('accounts.create_organisation')">
    <x-page-header :title="__('accounts.create_organisation')"><x-slot:actions><x-button :href="route('superadmin.organisations.index')" variant="outline-secondary">{{ __('accounts.back_organisations') }}</x-button></x-slot:actions></x-page-header>
    <div class="d-grid gap-3"><div><x-card>
        <x-form :action="route('superadmin.organisations.store')"><x-form-input name="name" :label="__('accounts.organisation_name')" maxlength="255" required autofocus /><div class="d-grid"><x-button type="submit">{{ __('accounts.create_organisation') }}</x-button></div></x-form>
    </x-card></div></div>
</x-layouts.app>
