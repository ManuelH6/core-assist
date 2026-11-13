@props([
    'title',
    'description' => null,
    'icon' => 'clipboard',
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-state-icon">
        <x-icon :name="$icon" size="24" />
    </div>
    <div class="empty-state-title">{{ $title }}</div>
    @if($description)
        <p class="empty-state-text">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="empty-state-actions">
            {{ $slot }}
        </div>
    @endif
</div>
