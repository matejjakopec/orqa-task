@props(['name', 'label', 'options', 'value' => null, 'placeholder' => null, 'bag' => 'default'])
@php
    $id = $attributes->get('id', 'field-'.$name);
    $hasError = $errors->getBag($bag)->has($name);
    $selected = old($name, $value);
    $selected = is_scalar($selected) ? (string) $selected : '';
@endphp
<div>
    <label class="form-label fw-medium" for="{{ $id }}">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}" aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if($hasError) aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except(['id', 'aria-invalid', 'aria-describedby'])->class(['form-select', 'is-invalid' => $hasError]) }}>
        <option value="">{{ $placeholder ?? __('accounts.choose') }}</option>
        @foreach($options as $key => $text)<option value="{{ $key }}" @selected($selected === (string) $key)>{{ $text }}</option>@endforeach
    </select>
    <x-field-error :name="$name" :id="$id.'-error'" :bag="$bag" />
</div>
