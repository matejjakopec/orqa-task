@props(['title', 'description' => null])
<div {{ $attributes->class(['py-3']) }}>
    <h2 class="h6">{{ $title }}</h2>
    @if($description)<p class="text-body-secondary mb-0">{{ $description }}</p>@endif
    @isset($action)<div class="d-grid mt-3">{{ $action }}</div>@endisset
</div>
