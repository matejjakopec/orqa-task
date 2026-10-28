@props(['status'])
@php($variant = \App\Support\FilePresentation::statusVariant($status))
<span {{ $attributes->class(['badge', 'text-bg-'.$variant]) }}>{{ __('files.status_'.$status->value) }}</span>
