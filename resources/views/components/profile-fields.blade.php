@props(['user' => null])
<div class="profile-fields d-grid gap-2">
    <x-form-input name="first_name" :label="__('accounts.first_name')" :value="$user?->first_name" autocomplete="given-name" maxlength="255" required />
    <x-form-input name="last_name" :label="__('accounts.last_name')" :value="$user?->last_name" autocomplete="family-name" maxlength="255" required />
    <x-form-input name="address" :label="__('accounts.address')" :value="$user?->address" autocomplete="street-address" maxlength="255" />
    <x-form-input name="city" :label="__('accounts.city')" :value="$user?->city" autocomplete="address-level2" maxlength="255" />
    <x-form-input name="postal_code" :label="__('accounts.postal_code')" :value="$user?->postal_code" autocomplete="postal-code" maxlength="20" />
    <x-form-input name="country" :label="__('accounts.country')" :value="$user?->country" placeholder="HR" autocomplete="country" minlength="2" maxlength="2" />
</div>
