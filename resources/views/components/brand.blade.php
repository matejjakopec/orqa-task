@props(['href'])
<a href="{{ $href }}" {{ $attributes->class(['brand d-inline-flex align-items-center gap-2 text-decoration-none']) }}>
    <span>{{ __('ui.brand') }}</span>
</a>
