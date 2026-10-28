<x-layouts.guest :title="__('ui.reset_request')" :heading="__('ui.reset_request')">
    <x-form :action="route('password.email')">
        <x-form-input name="email" type="email" :label="__('ui.email')" autocomplete="email" autocapitalize="none" required autofocus />
        <x-button type="submit" class="w-100">{{ __('ui.send_reset') }}</x-button>
    </x-form>
    <div class="mt-3"><a href="{{ route('login') }}">{{ __('ui.back_to_login') }}</a></div>
</x-layouts.guest>
