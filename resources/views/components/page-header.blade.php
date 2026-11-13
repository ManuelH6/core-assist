@props([
    'title',
    'eyebrow' => null,
    'subtitle' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'page-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3']) }}>
    <div>
        @if($eyebrow)
            <div class="ba-eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1 class="page-title mb-1">
            @if($icon)
                <x-icon :name="$icon" size="24" />
            @endif
            {{ $title }}<span class="ba-dot">.</span>
        </h1>
        @if($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
    @if(isset($actions))
        <div class="d-flex align-items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
