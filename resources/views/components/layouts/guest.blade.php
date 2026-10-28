@props(['title', 'heading', 'description' => null])
<x-layouts.base :title="$title">
    <main id="main-content" class="container px-3 py-5">
        <div class="auth-content">
            <div class="mb-3"><x-brand :href="route('login')" /></div>
            <section class="card p-3 p-sm-4">
                <header class="mb-3">
                    <h1 class="h4 mb-2">{{ $heading }}</h1>
                    @if ($description)<p class="text-body-secondary mb-0">{{ $description }}</p>@endif
                </header>
                <x-flash-messages />
                {{ $slot }}
            </section>
        </div>
    </main>
</x-layouts.base>
