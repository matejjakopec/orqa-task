@props(['users', 'area'])
<div class="table-responsive">
    <table class="table align-middle mb-0 account-table">
        <thead><tr><th scope="col">{{ __('accounts.name') }}</th><th scope="col">{{ __('ui.organisation') }}</th><th scope="col">{{ __('ui.roles') }}</th><th scope="col">{{ __('accounts.status') }}</th><th scope="col"><span class="visually-hidden">{{ __('accounts.details') }}</span></th></tr></thead>
        <tbody>
            @foreach($users as $user)
                <tr>
                    <td><a class="fw-medium text-break" href="{{ route($area.'.users.show', $user->public_id) }}">{{ $user->display_name }}</a><div class="small text-body-secondary text-break">{{ $user->username }} · {{ $user->email }}</div></td>
                    <td class="text-break">{{ $user->organisation?->name ?? __('ui.global_account') }}</td>
                    <td><div class="d-flex flex-wrap gap-1">@foreach($user->roles as $role)<span class="badge role-badge">{{ __('ui.role_'.$role->name->value) }}</span>@endforeach</div></td>
                    <td><x-account-status :user="$user" /></td>
                    <td><a href="{{ route($area.'.users.show', $user->public_id) }}" class="btn btn-sm btn-outline-secondary">{{ __('accounts.details') }}</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
