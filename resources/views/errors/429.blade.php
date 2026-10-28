<x-layouts.guest :title="__('ui.too_many_attempts')" :heading="__('ui.too_many_attempts')" :description="__('ui.too_many_description')">
    @auth
        <x-button :href="route('home')" variant="outline-primary">{{ __('ui.back_to_application') }}</x-button>
    @else
        <x-button :href="route('login')" variant="outline-primary">{{ __('ui.back_to_login') }}</x-button>
    @endauth
</x-layouts.guest>
