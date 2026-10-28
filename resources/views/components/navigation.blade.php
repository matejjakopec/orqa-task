<nav class="navbar navbar-expand-xl app-navigation" aria-label="{{ __('ui.navigation') }}">
    <div class="container-xl px-3 px-sm-4 gap-3">
        <x-brand :href="route($hasFileAccess ? 'files.index' : 'admin.users.index')" />
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#app-navigation" aria-controls="app-navigation" aria-expanded="false" aria-label="{{ __('ui.toggle_navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="app-navigation">
            <div class="navbar-nav gap-xl-2 ms-xl-4">
                @if($hasFileAccess)<x-nav-link :href="route('files.index')" :active="request()->routeIs('files.*')">{{ __($hasSuperadminRole ? 'files.all_files' : 'files.title') }}</x-nav-link>@endif
                @if($hasAdminRole)
                    <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">{{ __('accounts.local_users') }}</x-nav-link>
                @endif
                @if($hasSuperadminRole)
                    <x-nav-link :href="route('superadmin.users.index')" :active="request()->routeIs('superadmin.users.*')">{{ __('accounts.all_users') }}</x-nav-link>
                    <x-nav-link :href="route('superadmin.organisations.index')" :active="request()->routeIs('superadmin.organisations.*')">{{ __('accounts.organisations') }}</x-nav-link>
                @endif
                <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">{{ __('accounts.profile') }}</x-nav-link>
            </div>
            <div class="nav-account d-flex flex-column flex-xl-row align-items-xl-center gap-3 ms-xl-auto pt-3 pt-xl-0">
                <span class="text-body-secondary text-break small">{{ auth()->user()->display_name }}</span>
                <form action="{{ route('logout') }}" method="POST" data-prevent-repeat>
                    @csrf
                    <x-button variant="outline-secondary" type="submit">{{ __('ui.logout') }}</x-button>
                </form>
            </div>
        </div>
    </div>
</nav>
