@props([
    'label',
    'value',
    'subtext' => null,
    'variant' => 'default',
    'progress' => null,
])

@php
    $valueColorClass = match($variant) {
        'primary' => 'stat-value-primary',
        'success' => 'stat-value-success',
        'warning' => 'stat-value-warning',
        'danger' => 'text-danger',
        default => '',
    };
@endphp

<div {{ $attributes->merge(['class' => 'card h-100 border shadow-sm']) }}>
    <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
        <div>
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value {{ $valueColorClass }}">
                {{ $value }}
            </div>
            @if($progress !== null)
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar {{ $variant === 'danger' ? 'bg-danger' : ($variant === 'warning' ? 'bg-warning' : ($variant === 'success' ? 'bg-success' : 'bg-primary')) }}" 
                         role="progressbar" 
                         style="width: {{ min($progress, 100) }}%;" 
                         aria-valuenow="{{ min($progress, 100) }}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            @endif
        </div>
        @if($subtext)
            <div class="text-muted small mt-1 {{ $variant === 'danger' ? 'text-danger' : '' }}">
                {{ $subtext }}
            </div>
        @endif
    </div>
</div>
