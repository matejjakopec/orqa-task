<x-layouts.guest :title="__('ui.reset_password')" :heading="__('ui.reset_password')" :description="__('ui.password_hint')">
    <x-form :action="route('password.update')">
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form-input name="email" type="email" :label="__('ui.email')" :value="$email" autocomplete="email" required />
        <x-form-input name="password" type="password" :label="__('ui.new_password')" autocomplete="new-password" minlength="12" required autofocus />
        <x-form-input name="password_confirmation" type="password" :label="__('ui.password_confirmation')" autocomplete="new-password" minlength="12" required />
        <x-button type="submit" class="w-100">{{ __('ui.save_password') }}</x-button>
    </x-form>
    <div class="mt-3"><a href="{{ route('login') }}">{{ __('ui.back_to_login') }}</a></div>
</x-layouts.guest>
