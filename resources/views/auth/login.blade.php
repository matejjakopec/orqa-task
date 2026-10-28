<x-layouts.guest :title="__('ui.login')" :heading="__('ui.login')">
    <x-form :action="route('login.store')">
        <x-form-input name="username" :label="__('ui.username')" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="50" required autofocus />
        <x-form-input name="password" type="password" :label="__('ui.password')" autocomplete="current-password" required />
        <div class="d-grid gap-2">
            <x-checkbox name="remember" :label="__('ui.remember')" />
            <a href="{{ route('password.request') }}">{{ __('ui.forgot_password') }}</a>
        </div>
        <x-button type="submit" class="w-100">{{ __('ui.login') }}</x-button>
    </x-form>
</x-layouts.guest>
