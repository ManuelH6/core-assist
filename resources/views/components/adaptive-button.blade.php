@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
    'icon' => null,
    'iconSize' => 14,
    'compactOnMobile' => false,
])

@php
    $classes = "btn btn-{$variant}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <x-icon :name="$icon" :size="$iconSize" />
        @endif
        @if($compactOnMobile)
            <span class="d-none d-sm-inline">{{ $slot }}</span>
        @else
            <span>{{ $slot }}</span>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <x-icon :name="$icon" :size="$iconSize" />
        @endif
        @if($compactOnMobile)
            <span class="d-none d-sm-inline">{{ $slot }}</span>
        @else
            <span>{{ $slot }}</span>
        @endif
    </button>
@endif
