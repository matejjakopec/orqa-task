@props(['action', 'method' => 'POST', 'bag' => 'default'])
@php($method = strtoupper($method))
<form action="{{ $action }}" method="{{ $method === 'GET' ? 'GET' : 'POST' }}" {{ $attributes->class(['d-grid gap-3']) }} data-prevent-repeat>
    @if($method !== 'GET')
        @csrf
        @if($method !== 'POST') @method($method) @endif
    @endif
    <x-validation-summary :bag="$bag" />
    {{ $slot }}
</form>
