@props(['name', 'label', 'checked' => false, 'bag' => 'default'])
@php
    $id = $attributes->get('id', 'field-'.$name);
    $hasError = $errors->getBag($bag)->has($name);
    $isChecked = in_array(old($name, $checked), [true, 1, '1', 'true', 'on'], true);
@endphp
<div class="form-check">
    <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked($isChecked)
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if($hasError) aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['id', 'aria-invalid', 'aria-describedby'])->class(['form-check-input', 'is-invalid' => $hasError]) }}>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    <x-field-error :name="$name" :id="$id.'-error'" :bag="$bag" />
</div>
