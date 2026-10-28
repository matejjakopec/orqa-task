<x-layouts.base :title="__('files.storage_unavailable_title')">
    <main id="main-content" class="container py-5"><x-card><x-empty-state :title="__('files.storage_unavailable_title')" :description="__('files.storage_unavailable')"><x-slot:action><x-button :href="route('home')">{{ __('ui.back_to_application') }}</x-button></x-slot:action></x-empty-state></x-card></main>
</x-layouts.base>
