{{-- Shows any status enum with its own label and colour tone. --}}
@props(['value'])
@if ($value)
    <span class="badge-{{ $value->tone() }}">{{ $value->label() }}</span>
@endif
