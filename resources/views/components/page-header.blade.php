@props(['title', 'description' => null])
<header {{ $attributes->class(['d-grid gap-3 mb-3']) }}>
    <div class="min-width-0">
        <h1 class="h4 text-break mb-2">{{ $title }}</h1>
        @if($description)<p class="text-body-secondary mb-0">{{ $description }}</p>@endif
    </div>
    @isset($actions)<div class="page-actions d-grid gap-2">{{ $actions }}</div>@endisset
</header>
