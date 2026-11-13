@props([
    'title',
    'titleUrl' => null,
    'subtitle' => null,
    'status' => null,
    'statusVariant' => null,
    'actionUrl' => null,
    'actionLabel' => 'Consulter &rarr;',
    'isAlert' => false,
])

<div {{ $attributes->merge(['class' => 'mobile-data-card ' . ($isAlert ? 'border-danger' : '')]) }}>
    <div class="mobile-data-card-header">
        <div>
            @if($titleUrl)
                <a href="{{ $titleUrl }}" class="mobile-data-card-title">
                    {{ $title }}
                </a>
            @else
                <div class="mobile-data-card-title">{{ $title }}</div>
            @endif
            @if($subtitle)
                <div class="text-muted small mt-1">{{ $subtitle }}</div>
            @endif
        </div>
        @if(isset($badge))
            {{ $badge }}
        @elseif($status)
            <span class="badge {{ $statusVariant ? 'bg-' . $statusVariant : 'bg-light text-dark border' }}">
                {{ $status }}
            </span>
        @endif
    </div>

    @if(isset($grid))
        <div class="mobile-data-grid">
            {{ $grid }}
        </div>
    @endif

    {{ $slot }}

    @if(isset($footer))
        {{ $footer }}
    @elseif($actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-outline-secondary btn-sm w-100">
            {!! $actionLabel !!}
        </a>
    @endif
</div>
