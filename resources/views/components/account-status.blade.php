@props(['user'])
@if($user->disabled_at !== null)
    <span class="badge text-bg-secondary">{{ __('accounts.disabled') }}</span>
@elseif($user->activated_at === null)
    <span class="badge text-bg-warning">{{ __('accounts.pending') }}</span>
@else
    <span class="badge text-bg-success">{{ __('accounts.active') }}</span>
@endif
