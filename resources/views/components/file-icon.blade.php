@props(['extension' => null])
<span {{ $attributes->class(['file-icon']) }}><i class="bi {{ \App\Support\FilePresentation::icon($extension) }}" aria-hidden="true"></i></span>
