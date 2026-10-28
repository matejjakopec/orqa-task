@props(['title'])
<x-layouts.base :title="$title">
    <x-navigation />
    <main id="main-content" class="container-xl px-3 px-sm-4 py-4">
        <x-flash-messages />
        {{ $slot }}
    </main>
</x-layouts.base>
