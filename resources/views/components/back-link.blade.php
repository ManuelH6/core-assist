@props([
    'href',
    'label' => null,
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'back-link mb-3']) }}>
    &#8701; {{ $label ?? $slot }}
</a>
