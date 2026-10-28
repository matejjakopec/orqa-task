@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'bag' => 'default'])
@php
    $id = $attributes->get('id', 'field-'.str_replace(['.', '[', ']'], ['-', '-', ''], $name));
    $hasError = $errors->getBag($bag)->has($name);
    $inputValue = old($name, $value);
    $inputValue = is_scalar($inputValue) ? (string) $inputValue : '';
    $describedBy = trim(($attributes->get('aria-describedby', '')).' '.($hint ? $id.'-hint' : '').' '.($hasError ? $id.'-error' : ''));
@endphp
<div>
    <label class="form-label fw-medium" for="{{ $id }}">{{ $label }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if(! in_array($type, ['password', 'file'], true)) value="{{ $inputValue }}" @endif
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except(['id', 'aria-invalid', 'aria-describedby'])->class(['form-control', 'is-invalid' => $hasError]) }}>
    @if($hint)<div class="form-text" id="{{ $id }}-hint">{{ $hint }}</div>@endif
    <x-field-error :name="$name" :id="$id.'-error'" :bag="$bag" />
</div>
