@props(['name', 'id', 'bag' => 'default'])
@if($errors->getBag($bag)->has($name))
    <div id="{{ $id }}" class="invalid-feedback d-block">{{ $errors->getBag($bag)->first($name) }}</div>
@endif
