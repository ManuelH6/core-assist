@props([
    'cancelUrl',
    'cancelLabel' => 'Annuler',
    'submitLabel' => 'Enregistrer',
    'submitIcon' => 'check',
])

<div {{ $attributes->merge(['class' => 'pt-3 border-top d-flex flex-column flex-sm-row justify-content-sm-end align-items-stretch align-items-sm-center gap-2']) }}>
    <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary order-2 order-sm-1">
        {{ $cancelLabel }}
    </a>
    <button type="submit" class="btn btn-primary order-1 order-sm-2">
        @if($submitIcon)
            <x-icon :name="$submitIcon" size="16" />
        @endif
        {{ $submitLabel }}
    </button>
</div>
