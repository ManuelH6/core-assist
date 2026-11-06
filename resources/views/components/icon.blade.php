@props([
    'name',
    'size' => 16,
    'class' => '',
])

@php
    $style = "width: {$size}px; height: {$size}px; display: inline-block; vertical-align: -0.15em; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;";
@endphp

@switch($name)
    @case('alert-triangle')
    @case('warning')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        @break

    @case('alert-circle')
    @case('danger')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        @break

    @case('briefcase')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        @break

    @case('users')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        @break

    @case('user')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        @break

    @case('clipboard')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
        @break

    @case('chart')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        @break

    @case('clock')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        @break

    @case('check')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><polyline points="20 6 9 17 4 12"/></svg>
        @break

    @case('plus')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        @break

    @case('lock')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        @break

    @case('flame')
    @case('fire')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
        @break

    @case('play')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><polygon points="6 3 20 12 6 21 6 3"/></svg>
        @break

    @case('refresh')
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/><path d="M3 21v-5h5"/></svg>
        @break

    @default
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="{{ $style }}" class="{{ $class }}"><circle cx="12" cy="12" r="10"/></svg>
@endswitch
