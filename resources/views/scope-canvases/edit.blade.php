@extends('layouts.app')

@section('title', 'Définition du Périmètre - ' . $mission->title)

@section('content')
@php
    $initialScopeItems = array_values(old('scope_items', $data['scope_items'] ?? []));
@endphp

<div class="container py-4">
    <div class="mb-3">
        <x-back-link :href="route('missions.show', $mission)" label="Retour à la mission" />
    </div>

    <div class="page-header mb-4">
        <div class="ba-eyebrow">DIAGNOSTIC 360° &bull; OUTIL 02</div>
        <h1 class="page-title mb-1">Diagnostic Scope Canvas™<span class="ba-dot">.</span></h1>
        <p class="page-subtitle">
            Définition du périmètre d'investigation : ajoutez un à un les axes à examiner, précisez leur statut et leur niveau de profondeur.
        </p>
    </div>

    @if(session('error'))
        <div class="alert alert-danger mb-4" role="alert">{{ session('error') }}</div>
    @endif

    <div id="scope-client-error" class="alert alert-danger mb-4 d-none" role="alert"></div>

    {{-- Rappel du cadre issu du Mission Framing Sheet --}}
    @if(!empty($data['initial_data']['framing_sheet']['specific_objective']))
        <div class="card border border-primary border-opacity-25 bg-primary bg-opacity-10 mb-4 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <x-icon name="clipboard" size="18" class="text-primary" />
                    <h2 class="h6 mb-0 fw-bold text-primary">Objectifs opérationnels de référence (MFS)</h2>
                </div>
                <div class="small text-secondary mb-1">
                    <strong>Objectif spécifique :</strong> {{ $data['initial_data']['framing_sheet']['specific_objective'] }}
                </div>
                @if(!empty($data['initial_data']['framing_sheet']['organization_context']))
                    <div class="small text-secondary">
                        <strong>Contexte :</strong> {{ $data['initial_data']['framing_sheet']['organization_context'] }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    <form id="scope-canvas-form" action="{{ route('missions.scope-canvas.store', $mission) }}" method="POST">
        @csrf

        {{-- Bloc d'ajout un à un avec retrait progressif des axes déjà choisis --}}
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div>
                    <h2 class="h6 mb-0 fw-bold">Ajouter un axe au périmètre</h2>
                    <p class="small text-muted mb-0">Sélectionnez un axe du référentiel à la fois. Les axes déjà ajoutés sont retirés automatiquement de la liste.</p>
                </div>
                <div>
                    <span id="available-count-badge" class="badge bg-light text-dark border">0 disponible</span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div id="builder-error" class="alert alert-warning py-2 px-3 small d-none mb-3" role="alert"></div>

                <div id="builder-exhausted-notice" class="alert alert-info py-2 px-3 small d-none mb-0" role="status">
                    Tous les axes du référentiel méthodologique ont été ajoutés au périmètre ci-dessous.
                </div>

                <div id="builder-controls">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="builder-domain" class="form-label">Domaine (filtre)</label>
                            <select id="builder-domain" class="form-select">
                                <option value="">Tous les domaines</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-8">
                            <label for="builder-axis-item" class="form-label">Axe &amp; Sous-axe à ajouter <span class="text-danger">*</span></label>
                            <select id="builder-axis-item" class="form-select">
                                <option value="">-- Sélectionner un axe --</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="builder-status" class="form-label">Statut</label>
                            <select id="builder-status" class="form-select">
                                <option value="included" selected>Inclus</option>
                                <option value="excluded">Exclu</option>
                                <option value="to_confirm">À confirmer</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="builder-depth" class="form-label">Profondeur</label>
                            <select id="builder-depth" class="form-select">
                                <option value="standard" selected>Standard</option>
                                <option value="approfondi">Approfondi</option>
                                <option value="rapide">Rapide</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6" id="builder-observation-group">
                            <label for="builder-observation" class="form-label">Observation</label>
                            <input type="text" id="builder-observation" class="form-control" placeholder="Précision ou point d'attention (optionnel)">
                        </div>

                        <div class="col-12 d-none" id="builder-justification-group">
                            <label for="builder-justification" class="form-label">Justification de l'exclusion <span class="text-danger">*</span></label>
                            <input type="text" id="builder-justification" class="form-control" placeholder="Motif explicite obligatoire pour un axe exclu">
                        </div>

                        <div class="col-12 d-flex justify-content-md-end pt-1">
                            <button type="button" id="btn-add-scope-item" class="btn btn-primary w-100 w-md-auto">
                                <x-icon name="plus" size="16" /> Ajouter au périmètre
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Liste des axes retenus dans le périmètre --}}
        <div class="card border shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                <div>
                    <h2 class="h6 mb-0 fw-bold">Axes ajoutés au périmètre</h2>
                    <p class="small text-muted mb-0">Au moins un axe avec le statut <strong>Inclus</strong> est requis pour enregistrer.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2" id="scope-summary-badges">
                    <span class="badge bg-light text-dark border" id="badge-total-count">0 élément</span>
                    <span class="badge bg-success" id="badge-included-count">0 inclus</span>
                    <span class="badge bg-secondary text-white" id="badge-excluded-count">0 exclu</span>
                    <span class="badge bg-warning text-dark" id="badge-toconfirm-count">0 à confirmer</span>
                </div>
            </div>

            <div class="card-body p-0">
                <div id="scope-empty-state" class="text-center py-5 px-3 text-muted">
                    <p class="mb-1 fw-semibold text-dark">Aucun axe sélectionné pour le moment.</p>
                    <p class="small mb-0">Utilisez le bloc ci-dessus pour ajouter un à un les axes composant le périmètre du diagnostic.</p>
                </div>

                {{-- Vue tableau structuré sur desktop (>= 768px) --}}
                <div id="scope-desktop-wrapper" class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 24%;" class="ps-3">Domaine &amp; Axe</th>
                                <th scope="col" style="width: 22%;">Sous-axe / Élément</th>
                                <th scope="col" style="width: 14%;">Statut</th>
                                <th scope="col" style="width: 14%;">Profondeur</th>
                                <th scope="col" style="width: 20%;">Justification (si exclu) &amp; Notes</th>
                                <th scope="col" style="width: 6%;" class="pe-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="scope-desktop-tbody"></tbody>
                    </table>
                </div>

                {{-- Vue cartes empilées sur mobile (< 768px) --}}
                <div id="scope-mobile-cards" class="mobile-card-list d-md-none p-3 mb-0 d-none"></div>
            </div>

            {{-- Source canonique unique des champs soumis au serveur --}}
            <div id="scope-items-hidden-inputs" aria-hidden="true"></div>

            <div class="card-footer bg-white p-3 border-top">
                <x-form-actions :cancel-url="route('missions.show', $mission)" submit-label="Enregistrer le périmètre" />
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const reference = @json($data['reference']);
    const initialItems = @json($initialScopeItems);

    const catalogItems = [];
    Object.entries(reference).forEach(([domainKey, domainData]) => {
        const domainLabel = domainData.label;
        Object.entries(domainData.axes || {}).forEach(([axisKey, axisData]) => {
            const axisLabel = axisData.label;
            Object.entries(axisData.subaxes || {}).forEach(([subaxisKey, subaxisLabel]) => {
                catalogItems.push({
                    key: `${domainLabel}|||${axisLabel}|||${subaxisLabel}`,
                    domainKey,
                    domain: domainLabel,
                    axisKey,
                    axis: axisLabel,
                    subaxisKey,
                    subaxis: subaxisLabel,
                });
            });
        });
    });

    let scopeItems = (Array.isArray(initialItems) ? initialItems : []).map((item) => ({
        id: item.id || '',
        domain: String(item.domain || ''),
        axis: String(item.axis || ''),
        subaxis: item.subaxis ? String(item.subaxis) : '',
        element: item.element ? String(item.element) : (item.subaxis ? String(item.subaxis) : ''),
        status: ['included', 'excluded', 'to_confirm'].includes(item.status) ? item.status : 'included',
        depth: ['standard', 'approfondi', 'rapide'].includes(item.depth) ? item.depth : 'standard',
        justification: item.justification ? String(item.justification) : '',
        observation: item.observation ? String(item.observation) : '',
    }));

    const formEl = document.getElementById('scope-canvas-form');
    const clientErrorEl = document.getElementById('scope-client-error');
    const builderErrorEl = document.getElementById('builder-error');
    const builderControlsEl = document.getElementById('builder-controls');
    const builderExhaustedEl = document.getElementById('builder-exhausted-notice');
    const availableCountBadgeEl = document.getElementById('available-count-badge');

    const builderDomainEl = document.getElementById('builder-domain');
    const builderAxisItemEl = document.getElementById('builder-axis-item');
    const builderStatusEl = document.getElementById('builder-status');
    const builderDepthEl = document.getElementById('builder-depth');
    const builderJustificationGroupEl = document.getElementById('builder-justification-group');
    const builderJustificationEl = document.getElementById('builder-justification');
    const builderObservationEl = document.getElementById('builder-observation');
    const addBtnEl = document.getElementById('btn-add-scope-item');

    const emptyStateEl = document.getElementById('scope-empty-state');
    const desktopWrapperEl = document.getElementById('scope-desktop-wrapper');
    const desktopTbodyEl = document.getElementById('scope-desktop-tbody');
    const mobileCardsEl = document.getElementById('scope-mobile-cards');
    const hiddenInputsEl = document.getElementById('scope-items-hidden-inputs');

    const badgeTotalEl = document.getElementById('badge-total-count');
    const badgeIncludedEl = document.getElementById('badge-included-count');
    const badgeExcludedEl = document.getElementById('badge-excluded-count');
    const badgeToConfirmEl = document.getElementById('badge-toconfirm-count');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function itemKey(item) {
        return `${item.domain}|||${item.axis}|||${item.subaxis || ''}`;
    }

    function getAvailableCatalogItems() {
        const chosenKeys = new Set(scopeItems.map(itemKey));
        return catalogItems.filter((entry) => !chosenKeys.has(entry.key));
    }

    function showBuilderError(message) {
        builderErrorEl.textContent = message;
        builderErrorEl.classList.remove('d-none');
    }

    function clearBuilderError() {
        builderErrorEl.textContent = '';
        builderErrorEl.classList.add('d-none');
    }

    function showClientError(message) {
        clientErrorEl.textContent = message;
        clientErrorEl.classList.remove('d-none');
        clientErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearClientError() {
        clientErrorEl.textContent = '';
        clientErrorEl.classList.add('d-none');
    }

    function toggleBuilderJustification() {
        const isExcluded = builderStatusEl.value === 'excluded';
        builderJustificationGroupEl.classList.toggle('d-none', !isExcluded);
        if (!isExcluded) {
            builderJustificationEl.value = '';
        }
    }

    function renderBuilderSelectors() {
        const available = getAvailableCatalogItems();
        availableCountBadgeEl.textContent = `${available.length} disponible${available.length > 1 ? 's' : ''}`;

        const hasAvailable = available.length > 0;
        builderControlsEl.classList.toggle('d-none', !hasAvailable);
        builderExhaustedEl.classList.toggle('d-none', hasAvailable);

        if (!hasAvailable) {
            builderDomainEl.innerHTML = '<option value="">Aucun domaine disponible</option>';
            builderAxisItemEl.innerHTML = '<option value="">Aucun axe disponible</option>';
            return;
        }

        const previousDomain = builderDomainEl.value;
        const domainMap = new Map();
        available.forEach((entry) => {
            domainMap.set(entry.domain, (domainMap.get(entry.domain) || 0) + 1);
        });

        let domainOptionsHtml = `<option value="">Tous les domaines (${available.length})</option>`;
        domainMap.forEach((count, domainName) => {
            const selected = previousDomain === domainName ? 'selected' : '';
            domainOptionsHtml += `<option value="${escapeHtml(domainName)}" ${selected}>${escapeHtml(domainName)} (${count})</option>`;
        });
        builderDomainEl.innerHTML = domainOptionsHtml;

        if (!domainMap.has(previousDomain)) {
            builderDomainEl.value = '';
        }

        renderAxisDropdown(available);
    }

    function renderAxisDropdown(availableItems = null) {
        const available = availableItems || getAvailableCatalogItems();
        const selectedDomain = builderDomainEl.value;
        const filtered = selectedDomain
            ? available.filter((entry) => entry.domain === selectedDomain)
            : available;

        const grouped = new Map();
        filtered.forEach((entry) => {
            const groupLabel = selectedDomain ? entry.axis : `${entry.domain} — ${entry.axis}`;
            if (!grouped.has(groupLabel)) {
                grouped.set(groupLabel, []);
            }
            grouped.get(groupLabel).push(entry);
        });

        let html = '<option value="">-- Sélectionner un axe / sous-axe --</option>';
        grouped.forEach((entries, groupLabel) => {
            html += `<optgroup label="${escapeHtml(groupLabel)}">`;
            entries.forEach((entry) => {
                html += `<option value="${escapeHtml(entry.key)}">${escapeHtml(entry.subaxis)}</option>`;
            });
            html += '</optgroup>';
        });

        builderAxisItemEl.innerHTML = html;
        builderAxisItemEl.value = '';
    }

    function syncHiddenInputs() {
        let html = '';
        scopeItems.forEach((item, index) => {
            html += `
                <input type="hidden" name="scope_items[${index}][id]" value="${escapeHtml(item.id)}">
                <input type="hidden" name="scope_items[${index}][domain]" value="${escapeHtml(item.domain)}">
                <input type="hidden" name="scope_items[${index}][axis]" value="${escapeHtml(item.axis)}">
                <input type="hidden" name="scope_items[${index}][subaxis]" value="${escapeHtml(item.subaxis)}">
                <input type="hidden" name="scope_items[${index}][element]" value="${escapeHtml(item.element)}">
                <input type="hidden" name="scope_items[${index}][status]" value="${escapeHtml(item.status)}">
                <input type="hidden" name="scope_items[${index}][depth]" value="${escapeHtml(item.depth)}">
                <input type="hidden" name="scope_items[${index}][justification]" value="${escapeHtml(item.justification)}">
                <input type="hidden" name="scope_items[${index}][observation]" value="${escapeHtml(item.observation)}">
            `;
        });
        hiddenInputsEl.innerHTML = html;
    }

    function updateSummaryBadges() {
        const total = scopeItems.length;
        const included = scopeItems.filter((i) => i.status === 'included').length;
        const excluded = scopeItems.filter((i) => i.status === 'excluded').length;
        const toConfirm = scopeItems.filter((i) => i.status === 'to_confirm').length;

        badgeTotalEl.textContent = `${total} élément${total > 1 ? 's' : ''}`;
        badgeIncludedEl.textContent = `${included} inclus`;
        badgeExcludedEl.textContent = `${excluded} exclu${excluded > 1 ? 's' : ''}`;
        badgeToConfirmEl.textContent = `${toConfirm} à confirmer`;
    }

    function statusBadgeHtml(status) {
        if (status === 'included') {
            return '<span class="badge bg-success">Inclus</span>';
        }
        if (status === 'excluded') {
            return '<span class="badge bg-light text-danger border border-danger">Exclu</span>';
        }
        return '<span class="badge bg-warning text-dark">À confirmer</span>';
    }

    function renderSelectedList() {
        const hasItems = scopeItems.length > 0;
        emptyStateEl.classList.toggle('d-none', hasItems);
        desktopWrapperEl.classList.toggle('d-md-block', hasItems);
        mobileCardsEl.classList.toggle('d-none', !hasItems);

        if (!hasItems) {
            desktopTbodyEl.innerHTML = '';
            mobileCardsEl.innerHTML = '';
            return;
        }

        let desktopHtml = '';
        let mobileHtml = '';

        scopeItems.forEach((item, index) => {
            const isExcluded = item.status === 'excluded';
            const justificationBorder = isExcluded && !item.justification.trim() ? 'border-danger' : '';

            desktopHtml += `
                <tr data-item-index="${index}">
                    <td class="ps-3">
                        <div class="fw-bold text-dark">${escapeHtml(item.domain)}</div>
                        <div class="small text-muted">${escapeHtml(item.axis)}</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-secondary small">${escapeHtml(item.subaxis || item.axis)}</div>
                    </td>
                    <td>
                        <select class="form-select form-select-sm js-item-field" data-index="${index}" data-field="status" aria-label="Statut pour ${escapeHtml(item.subaxis || item.axis)}">
                            <option value="included" ${item.status === 'included' ? 'selected' : ''}>Inclus</option>
                            <option value="excluded" ${item.status === 'excluded' ? 'selected' : ''}>Exclu</option>
                            <option value="to_confirm" ${item.status === 'to_confirm' ? 'selected' : ''}>À confirmer</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select form-select-sm js-item-field" data-index="${index}" data-field="depth" aria-label="Profondeur pour ${escapeHtml(item.subaxis || item.axis)}">
                            <option value="standard" ${item.depth === 'standard' ? 'selected' : ''}>Standard</option>
                            <option value="approfondi" ${item.depth === 'approfondi' ? 'selected' : ''}>Approfondi</option>
                            <option value="rapide" ${item.depth === 'rapide' ? 'selected' : ''}>Rapide</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm mb-1 js-item-field ${justificationBorder}" data-index="${index}" data-field="justification" placeholder="${isExcluded ? 'Justification obligatoire *' : 'Justification si exclu'}" value="${escapeHtml(item.justification)}">
                        <input type="text" class="form-control form-control-sm js-item-field" data-index="${index}" data-field="observation" placeholder="Observation optionnelle" value="${escapeHtml(item.observation)}">
                    </td>
                    <td class="pe-3 text-end">
                        <button type="button" class="btn btn-outline-danger js-remove-item" data-index="${index}" title="Retirer cet axe" aria-label="Retirer ${escapeHtml(item.subaxis || item.axis)}" style="min-width: 44px; min-height: 44px; padding: 0 12px;">
                            &times;
                        </button>
                    </td>
                </tr>
            `;

            mobileHtml += `
                <div class="mobile-data-card" data-item-index="${index}">
                    <div class="mobile-data-card-header">
                        <div>
                            <div class="ba-eyebrow mb-1">${escapeHtml(item.domain)}</div>
                            <div class="mobile-data-card-title">${escapeHtml(item.axis)}</div>
                            <div class="text-secondary small mt-1">${escapeHtml(item.subaxis || item.element)}</div>
                        </div>
                        <div class="js-mobile-status-badge">${statusBadgeHtml(item.status)}</div>
                    </div>

                    <div class="mobile-data-grid">
                        <div class="mobile-data-item">
                            <label class="form-label small fw-semibold mb-1" for="mobile-status-${index}">Statut</label>
                            <select id="mobile-status-${index}" class="form-select js-item-field" data-index="${index}" data-field="status">
                                <option value="included" ${item.status === 'included' ? 'selected' : ''}>Inclus</option>
                                <option value="excluded" ${item.status === 'excluded' ? 'selected' : ''}>Exclu</option>
                                <option value="to_confirm" ${item.status === 'to_confirm' ? 'selected' : ''}>À confirmer</option>
                            </select>
                        </div>
                        <div class="mobile-data-item">
                            <label class="form-label small fw-semibold mb-1" for="mobile-depth-${index}">Profondeur</label>
                            <select id="mobile-depth-${index}" class="form-select js-item-field" data-index="${index}" data-field="depth">
                                <option value="standard" ${item.depth === 'standard' ? 'selected' : ''}>Standard</option>
                                <option value="approfondi" ${item.depth === 'approfondi' ? 'selected' : ''}>Approfondi</option>
                                <option value="rapide" ${item.depth === 'rapide' ? 'selected' : ''}>Rapide</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label small fw-semibold mb-1" for="mobile-justification-${index}">
                            Justification ${isExcluded ? '<span class="text-danger">*</span>' : '(si exclu)'}
                        </label>
                        <input id="mobile-justification-${index}" type="text" class="form-control js-item-field ${justificationBorder}" data-index="${index}" data-field="justification" placeholder="Raison de l'exclusion" value="${escapeHtml(item.justification)}">
                    </div>

                    <div>
                        <label class="form-label small fw-semibold mb-1" for="mobile-observation-${index}">Observations</label>
                        <input id="mobile-observation-${index}" type="text" class="form-control js-item-field" data-index="${index}" data-field="observation" placeholder="Précisions complémentaires" value="${escapeHtml(item.observation)}">
                    </div>

                    <button type="button" class="btn btn-outline-danger w-100 js-remove-item" data-index="${index}">
                        Retirer cet axe
                    </button>
                </div>
            `;
        });

        desktopTbodyEl.innerHTML = desktopHtml;
        mobileCardsEl.innerHTML = mobileHtml;
    }

    function renderAll() {
        renderBuilderSelectors();
        renderSelectedList();
        syncHiddenInputs();
        updateSummaryBadges();
    }

    function handleAddItem() {
        clearBuilderError();
        clearClientError();

        const selectedKey = builderAxisItemEl.value;
        if (!selectedKey) {
            showBuilderError('Veuillez sélectionner un axe ou sous-axe à ajouter.');
            builderAxisItemEl.focus();
            return;
        }

        const catalogEntry = catalogItems.find((entry) => entry.key === selectedKey);
        if (!catalogEntry) {
            showBuilderError('Élément introuvable dans le référentiel.');
            return;
        }

        const status = builderStatusEl.value;
        const depth = builderDepthEl.value;
        const justification = builderJustificationEl.value.trim();
        const observation = builderObservationEl.value.trim();

        if (status === 'excluded' && justification === '') {
            showBuilderError("Une justification explicite est obligatoire lorsque l'axe est marqué comme Exclu.");
            builderJustificationEl.focus();
            return;
        }

        scopeItems.push({
            id: '',
            domain: catalogEntry.domain,
            axis: catalogEntry.axis,
            subaxis: catalogEntry.subaxis,
            element: catalogEntry.subaxis,
            status,
            depth,
            justification,
            observation,
        });

        builderStatusEl.value = 'included';
        builderDepthEl.value = 'standard';
        builderJustificationEl.value = '';
        builderObservationEl.value = '';
        toggleBuilderJustification();

        renderAll();
    }

    function syncPeerInput(index, field, value, sourceEl) {
        const inputs = document.querySelectorAll(`.js-item-field[data-index="${index}"][data-field="${field}"]`);
        inputs.forEach((el) => {
            if (el !== sourceEl && el.value !== value) {
                el.value = value;
            }
        });
    }

    builderDomainEl.addEventListener('change', () => {
        clearBuilderError();
        renderAxisDropdown();
    });

    builderStatusEl.addEventListener('change', () => {
        clearBuilderError();
        toggleBuilderJustification();
    });

    addBtnEl.addEventListener('click', handleAddItem);

    [builderJustificationEl, builderObservationEl].forEach((inputEl) => {
        inputEl.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleAddItem();
            }
        });
    });

    document.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.js-remove-item');
        if (!removeBtn) {
            return;
        }
        const index = Number(removeBtn.dataset.index);
        if (Number.isInteger(index) && index >= 0 && index < scopeItems.length) {
            clearClientError();
            scopeItems.splice(index, 1);
            renderAll();
        }
    });

    document.addEventListener('change', (event) => {
        const fieldEl = event.target.closest('.js-item-field');
        if (!fieldEl || fieldEl.tagName !== 'SELECT') {
            return;
        }
        const index = Number(fieldEl.dataset.index);
        const field = fieldEl.dataset.field;
        if (!Number.isInteger(index) || !scopeItems[index]) {
            return;
        }
        clearClientError();
        scopeItems[index][field] = fieldEl.value;
        syncPeerInput(index, field, fieldEl.value, fieldEl);
        syncHiddenInputs();
        updateSummaryBadges();
        if (field === 'status') {
            renderSelectedList();
        }
    });

    document.addEventListener('input', (event) => {
        const fieldEl = event.target.closest('.js-item-field');
        if (!fieldEl || fieldEl.tagName === 'SELECT') {
            return;
        }
        const index = Number(fieldEl.dataset.index);
        const field = fieldEl.dataset.field;
        if (!Number.isInteger(index) || !scopeItems[index]) {
            return;
        }
        clearClientError();
        scopeItems[index][field] = fieldEl.value;
        syncPeerInput(index, field, fieldEl.value, fieldEl);
        const hiddenInput = hiddenInputsEl.querySelector(`input[name="scope_items[${index}][${field}]"]`);
        if (hiddenInput) {
            hiddenInput.value = fieldEl.value;
        }
    });

    formEl.addEventListener('submit', (event) => {
        clearClientError();
        syncHiddenInputs();

        if (scopeItems.length === 0) {
            event.preventDefault();
            showClientError('Le périmètre doit comporter au moins un élément.');
            return;
        }

        const missingJustification = scopeItems.find(
            (item) => item.status === 'excluded' && !String(item.justification || '').trim()
        );
        if (missingJustification) {
            event.preventDefault();
            showClientError(
                `L'élément '${missingJustification.axis}' (Domaine ${missingJustification.domain}) est exclu : une justification explicite est obligatoire.`
            );
            return;
        }

        const hasIncluded = scopeItems.some((item) => item.status === 'included');
        if (!hasIncluded) {
            event.preventDefault();
            showClientError('Le périmètre doit comporter au moins un élément avec le statut Inclus.');
        }
    });

    toggleBuilderJustification();
    renderAll();
});
</script>
@endpush
