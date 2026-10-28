@props(['title' => null, 'description' => null])
<section {{ $attributes->class(['card']) }}>
    <div class="card-body">
        @if($title)
            <header class="mb-3">
                <h2 class="h6 fw-semibold mb-2">{{ $title }}</h2>
                @if($description)<p class="text-body-secondary mb-0">{{ $description }}</p>@endif
            </header>
        @endif
        {{ $slot }}
    </div>
</section>
