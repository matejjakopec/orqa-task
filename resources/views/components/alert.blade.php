@props(['variant' => 'success'])
@php($variant = in_array($variant, ['success', 'info', 'warning', 'danger'], true) ? $variant : 'info')
<div {{ $attributes->class(['alert', 'alert-'.$variant]) }} role="{{ $variant === 'danger' ? 'alert' : 'status' }}">{{ $slot }}</div>
