<x-layouts.app :title="$user->display_name">
    <x-page-header :title="$user->display_name"><x-slot:actions><x-button :href="route($area.'.users.index')" variant="outline-secondary">{{ __('accounts.back_users') }}</x-button></x-slot:actions></x-page-header>
    <div class="d-grid gap-3">
        <div><x-card :title="__('ui.account_details')">
            <dl class="d-grid gap-3 mb-0 account-details">
                <div><dt>{{ __('ui.username') }}</dt><dd>{{ $user->username }}</dd></div>
                <div><dt>{{ __('ui.email') }}</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>{{ __('ui.organisation') }}</dt><dd>{{ $user->organisation?->name ?? __('ui.global_account') }}</dd></div>
                <div><dt>{{ __('accounts.status') }}</dt><dd><x-account-status :user="$user" /></dd></div>
                <div><dt>{{ __('ui.roles') }}</dt><dd class="d-flex flex-wrap gap-1">@foreach($user->roles as $role)<span class="badge role-badge">{{ __('ui.role_'.$role->name->value) }}</span>@endforeach</dd></div>
                <div><dt>{{ __('accounts.address') }}</dt><dd>{{ $user->address ?? '—' }}</dd></div>
                <div><dt>{{ __('accounts.city') }}</dt><dd>{{ $user->city ?? '—' }}</dd></div>
                <div><dt>{{ __('accounts.postal_code') }}</dt><dd>{{ $user->postal_code ?? '—' }}</dd></div>
                <div><dt>{{ __('accounts.country') }}</dt><dd>{{ $user->country ?? '—' }}</dd></div>
            </dl>
        </x-card></div>
        <div><x-card :title="__('accounts.storage')"><x-storage-summary :account="$user->storageAccount" />@if($area === 'superadmin' && $user->storageAccount)<div class="mt-4"><x-button :href="route('files.index', ['owner_id' => $user->id])" variant="outline-primary">{{ __('files.view_account_files') }}</x-button></div>@endif</x-card></div>
    </div>
</x-layouts.app>
