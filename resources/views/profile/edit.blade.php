<x-layouts.app :title="__('accounts.profile')">
    <x-page-header :title="__('accounts.profile')" />
    <div class="d-grid gap-3">
        <div><x-card>
            <x-form :action="route('profile.update')" method="PATCH">
                <x-profile-fields :user="$user" />
                <div class="form-actions d-grid"><x-button type="submit">{{ __('accounts.save_profile') }}</x-button></div>
            </x-form>
        </x-card></div>
        <x-card :title="__('accounts.storage')"><x-storage-summary :account="$user->storageAccount" /></x-card>
    </div>
</x-layouts.app>
