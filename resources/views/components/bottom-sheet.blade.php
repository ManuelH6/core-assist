@props([
    'id',
    'title',
    'icon' => null,
])

<div class="offcanvas offcanvas-bottom" tabindex="-1" id="{{ $id }}" aria-labelledby="{{ $id }}Label">
    <div class="offcanvas-header border-bottom py-3 px-4">
        <h5 class="offcanvas-title h6 fw-bold mb-0 d-flex align-items-center gap-2" id="{{ $id }}Label">
            @if($icon)
                <x-icon :name="$icon" size="18" />
            @endif
            {{ $title }}
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
    </div>
    <div class="offcanvas-body p-4">
        {{ $slot }}
    </div>
</div>
