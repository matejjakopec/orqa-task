@props(['bag' => 'default'])
@if($errors->getBag($bag)->any())
    <div class="alert alert-danger mb-0 validation-summary" role="alert" tabindex="-1" data-validation-summary>
        <p class="fw-semibold mb-2">{{ __('ui.validation_heading') }}</p>
        <ul class="mb-0 ps-3">
            @foreach($errors->getBag($bag)->messages() as $field => $messages)
                <li><a class="alert-link" href="#field-{{ str_replace(['.', '[', ']'], ['-', '-', ''], $field) }}" data-error-field="{{ $field }}">{{ $messages[0] }}</a></li>
            @endforeach
        </ul>
    </div>
@endif
