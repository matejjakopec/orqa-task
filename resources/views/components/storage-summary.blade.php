@props(['account' => null])
@if($account)
    <dl class="d-flex flex-wrap gap-2 column-gap-4 mb-2 small">
        <div class="d-flex gap-2"><dt class="fw-normal text-body-secondary mb-0">{{ __('accounts.quota') }}</dt><dd class="fw-semibold mb-0 text-nowrap">{{ \App\Support\ByteFormatter::format($account->quota_bytes) }}</dd></div>
        <div class="d-flex gap-2"><dt class="fw-normal text-body-secondary mb-0">{{ __('accounts.used') }}</dt><dd class="fw-semibold mb-0 text-nowrap">{{ \App\Support\ByteFormatter::format($account->used_bytes) }}</dd></div>
        <div class="d-flex gap-2"><dt class="fw-normal text-body-secondary mb-0">{{ __('accounts.reserved') }}</dt><dd class="fw-semibold mb-0 text-nowrap">{{ \App\Support\ByteFormatter::format($account->reserved_bytes) }}</dd></div>
        <div class="d-flex gap-2"><dt class="fw-normal text-body-secondary mb-0">{{ __('accounts.free') }}</dt><dd class="fw-semibold mb-0 text-nowrap">{{ \App\Support\ByteFormatter::format($account->quota_bytes - $account->used_bytes - $account->reserved_bytes) }}</dd></div>
    </dl>
    <progress class="storage-progress d-block w-100" max="{{ max(1, $account->quota_bytes) }}" value="{{ $account->used_bytes + $account->reserved_bytes }}" aria-label="{{ __('accounts.charged_storage') }}"></progress>
@else
    <p class="text-body-secondary mb-0">{{ __('accounts.no_storage') }}</p>
@endif
