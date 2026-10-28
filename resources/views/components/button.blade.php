@props(['type' => 'button', 'variant' => 'primary', 'href' => null])
@php($variant = in_array($variant, ['primary', 'secondary', 'outline-primary', 'outline-secondary', 'danger', 'outline-danger', 'link'], true) ? $variant : 'primary')
@if($href)
    <a href="{{ $href }}" {{ $attributes->class(['btn', 'btn-'.$variant]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['btn', 'btn-'.$variant]) }}>{{ $slot }}</button>
@endif
