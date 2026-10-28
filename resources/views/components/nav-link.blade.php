@props(['href', 'active' => false])
<a href="{{ $href }}" {{ $attributes->class(['nav-link rounded px-3', 'active' => $active]) }} @if($active) aria-current="page" @endif>{{ $slot }}</a>
