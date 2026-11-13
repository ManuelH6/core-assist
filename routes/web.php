<?php

use App\Http\Controllers\Auth\LoginController;
use App\Modules\Analysis\Http\Controllers\AnalysisController;
use App\Modules\ClientRelationship\Http\Controllers\ClientController;
use App\Modules\ClientRelationship\Http\Controllers\ClientPortalController;
use App\Modules\ClientRelationship\Http\Controllers\FramingController;
use App\Modules\ClientRelationship\Http\Controllers\RequestController;
use App\Modules\Diagnostic360Matrix\Http\Controllers\Diagnostic360MatrixController;
use App\Modules\DiagnosticArchivePack\Http\Controllers\DiagnosticArchivePackController;
use App\Modules\DiagnosticIntakeRegister\Http\Controllers\DiagnosticIntakeRegisterController;
use App\Modules\DiagnosticRestitutionDeck\Http\Controllers\DiagnosticRestitutionDeckController;
use App\Modules\DiagnosticScopeCanvas\Http\Controllers\DiagnosticScopeCanvasController;
use App\Modules\FinancialCollection\Http\Controllers\CollectionController;
use App\Modules\LiveDecisionCapture\Http\Controllers\LiveDecisionCaptureController;
use App\Modules\MissionFramingSheet\Http\Controllers\MissionFramingSheetController;
use App\Modules\NextStepRouter\Http\Controllers\NextStepRouterController;
use App\Modules\ProblemStructuringGrid\Http\Controllers\ProblemStructuringGridController;
use App\Modules\ProjectManagement\Http\Controllers\CollaboratorController;
use App\Modules\ProjectManagement\Http\Controllers\MissionConfigurationController;
use App\Modules\ProjectManagement\Http\Controllers\MissionController;
use App\Modules\ProjectManagement\Http\Controllers\TaskController;
use App\Modules\StakeholderInputGrid\Http\Controllers\StakeholderInputGridController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentification
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Portail Client (dépôt et suivi des demandes + décision sur cadrage)
Route::prefix('client-portal')->name('client-portal.')->middleware(['auth', 'role:client'])->group(function () {
    Route::get('/', [ClientPortalController::class, 'index'])->name('index');
    Route::get('requests/create', [ClientPortalController::class, 'createRequest'])->name('requests.create');
    Route::post('requests', [ClientPortalController::class, 'storeRequest'])->name('requests.store');
    Route::get('requests/{clientRequest}', [ClientPortalController::class, 'showRequest'])->name('requests.show');
    Route::post('requests/{clientRequest}/framings/{version}/decide', [ClientPortalController::class, 'decideFraming'])->name('requests.framings.decide');
});

// Relation Client & Cadrage (Cabinet : DG, Manager, Chef de mission)
Route::prefix('clients')->name('clients.')->middleware(['auth', 'role:dg,manager'])->group(function () {
    Route::get('/', [ClientController::class, 'index'])->name('index');
    Route::get('create', [ClientController::class, 'create'])->name('create');
    Route::post('/', [ClientController::class, 'store'])->name('store');
    Route::get('{client}', [ClientController::class, 'show'])->name('show');
    Route::post('{client}/users', [ClientController::class, 'storeUser'])->name('users.store');
});

Route::prefix('requests')->name('requests.')->middleware(['auth', 'role:dg,manager,mission_lead'])->group(function () {
    Route::get('/', [RequestController::class, 'index'])->name('index');
    Route::get('create', [RequestController::class, 'create'])->name('create');
    Route::post('/', [RequestController::class, 'store'])->name('store');
    Route::get('{clientRequest}', [RequestController::class, 'show'])->name('show');
    Route::put('{clientRequest}/understanding', [RequestController::class, 'updateUnderstanding'])->name('understanding.update');
    Route::post('{clientRequest}/status', [RequestController::class, 'updateStatus'])->name('status.update');
});

Route::prefix('framings')->name('framings.')->middleware(['auth', 'role:dg,manager,mission_lead'])->group(function () {
    Route::post('requests/{clientRequest}', [FramingController::class, 'store'])->name('store');
    Route::get('{framing}', [FramingController::class, 'show'])->name('show');
    Route::post('{framing}/versions', [FramingController::class, 'storeVersion'])->name('versions.store');
    Route::post('{framing}/versions/{version}/submit', [FramingController::class, 'submitVersion'])->name('versions.submit');
    Route::post('{framing}/versions/{version}/decision', [FramingController::class, 'recordDecision'])->name('versions.decision');
});

// Configuration des Outils, Équipe & Autorisations de la Mission (DG & Manager)
Route::prefix('missions/{mission}/configuration')->name('missions.configuration.')->middleware(['auth', 'role:dg,manager'])->group(function () {
    Route::get('/', [MissionConfigurationController::class, 'edit'])->name('edit');
    Route::put('/', [MissionConfigurationController::class, 'update'])->name('update');
});

// Espace Cabinet (bloqué pour les comptes clients)
Route::middleware('no.client')->group(function () {

    // Outils Financiers
    Route::resource('collections', CollectionController::class);
    Route::resource('analyses', AnalysisController::class)->only(['index', 'store', 'show']);

    // Gestion de Projet & Staffing Cabinet
    Route::resource('missions', MissionController::class);
    Route::resource('collaborators', CollaboratorController::class);
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    // Diagnostic 360° - Outil 01 : Mission Framing Sheet™
    Route::prefix('missions/{mission}/framing-sheet')->name('missions.framing-sheet.')->group(function () {
        Route::get('initial-data', [MissionFramingSheetController::class, 'initialData'])->name('initial-data');
        Route::get('/', [MissionFramingSheetController::class, 'show'])->name('show');
        Route::post('/', [MissionFramingSheetController::class, 'store'])->name('store');
        Route::put('/', [MissionFramingSheetController::class, 'update'])->name('update');
        Route::post('submit', [MissionFramingSheetController::class, 'submit'])->name('submit');
    });

    // Diagnostic 360° - Outil 02 : Diagnostic Scope Canvas™
    Route::prefix('missions/{mission}/scope-canvas')->name('missions.scope-canvas.')->group(function () {
        Route::get('initial-data', [DiagnosticScopeCanvasController::class, 'initialData'])->name('initial-data');
        Route::get('/', [DiagnosticScopeCanvasController::class, 'show'])->name('show');
        Route::post('/', [DiagnosticScopeCanvasController::class, 'store'])->name('store');
        Route::put('/', [DiagnosticScopeCanvasController::class, 'update'])->name('update');
        Route::post('submit', [DiagnosticScopeCanvasController::class, 'submit'])->name('submit');
        Route::post('validate', [DiagnosticScopeCanvasController::class, 'validateScope'])->name('validate');
    });

    // Diagnostic 360° - Outil 03 : Diagnostic Intake Register™
    Route::prefix('missions/{mission}/intake-register')->name('missions.intake-register.')->group(function () {
        Route::get('/', [DiagnosticIntakeRegisterController::class, 'show'])->name('show');
        Route::post('initialize', [DiagnosticIntakeRegisterController::class, 'initialize'])->name('initialize');
        Route::post('information', [DiagnosticIntakeRegisterController::class, 'storeInformation'])->name('information.store');
        Route::put('information/{information}', [DiagnosticIntakeRegisterController::class, 'updateInformation'])->name('information.update');
        Route::post('information/{information}/assign', [DiagnosticIntakeRegisterController::class, 'assign'])->name('information.assign');
        Route::post('information/{information}/status', [DiagnosticIntakeRegisterController::class, 'updateStatus'])->name('information.status');
        Route::post('received-items', [DiagnosticIntakeRegisterController::class, 'storeReceivedItem'])->name('received-items.store');
        Route::get('progress', [DiagnosticIntakeRegisterController::class, 'progress'])->name('progress');
    });

    // Diagnostic 360° - Outil 04 : Stakeholder Input Grid™
    Route::prefix('missions/{mission}/stakeholder-grid')->name('missions.stakeholder-grid.')->group(function () {
        Route::get('/', [StakeholderInputGridController::class, 'show'])->name('show');
        Route::post('initialize', [StakeholderInputGridController::class, 'initialize'])->name('initialize');
        Route::post('stakeholders', [StakeholderInputGridController::class, 'storeStakeholder'])->name('stakeholders.store');
        Route::put('stakeholders/{stakeholder}', [StakeholderInputGridController::class, 'updateStakeholder'])->name('stakeholders.update');
        Route::post('expected-contributions', [StakeholderInputGridController::class, 'storeExpectedContribution'])->name('expected-contributions.store');
        Route::post('expected-contributions/{contribution}/assign', [StakeholderInputGridController::class, 'assignMobilization'])->name('expected-contributions.assign');
        Route::post('expected-contributions/{contribution}/status', [StakeholderInputGridController::class, 'updateMobilizationStatus'])->name('expected-contributions.status');
        Route::post('received-contributions', [StakeholderInputGridController::class, 'storeReceivedContribution'])->name('received-contributions.store');
        Route::get('progress', [StakeholderInputGridController::class, 'progress'])->name('progress');
    });

    // Diagnostic 360° - Outil 05 : 360° Diagnostic Matrix™
    Route::prefix('missions/{mission}/diagnostic-matrix')->name('missions.diagnostic-matrix.')->group(function () {
        Route::get('/', [Diagnostic360MatrixController::class, 'show'])->name('show');
        Route::post('prepare', [Diagnostic360MatrixController::class, 'prepare'])->name('prepare');
        Route::post('assessments/{assessment}', [Diagnostic360MatrixController::class, 'updateAssessment'])->name('assessments.update');
        Route::post('assessments/{assessment}/evidence', [Diagnostic360MatrixController::class, 'linkEvidence'])->name('assessments.evidence');
        Route::post('assessments/{assessment}/findings', [Diagnostic360MatrixController::class, 'recordFinding'])->name('assessments.findings');
        Route::post('consolidate-axis', [Diagnostic360MatrixController::class, 'consolidateAxis'])->name('consolidate-axis');
        Route::post('consolidate-domain', [Diagnostic360MatrixController::class, 'consolidateDomain'])->name('consolidate-domain');
        Route::post('snapshot', [Diagnostic360MatrixController::class, 'generateSnapshot'])->name('snapshot');
    });

    // Diagnostic 360° - Outil 06 : Problem Structuring Grid™
    Route::prefix('missions/{mission}/problem-grid')->name('missions.problem-grid.')->group(function () {
        Route::get('/', [ProblemStructuringGridController::class, 'show'])->name('show');
        Route::post('prepare', [ProblemStructuringGridController::class, 'prepare'])->name('prepare');
        Route::post('problems', [ProblemStructuringGridController::class, 'storeProblem'])->name('problems.store');
        Route::put('problems/{problem}', [ProblemStructuringGridController::class, 'updateProblem'])->name('problems.update');
        Route::post('problems/{problem}/causes', [ProblemStructuringGridController::class, 'storeCause'])->name('problems.causes.store');
        Route::put('causes/{cause}/level', [ProblemStructuringGridController::class, 'updateCauseLevel'])->name('causes.level.update');
        Route::post('problems/{problem}/consequences', [ProblemStructuringGridController::class, 'storeConsequence'])->name('problems.consequences.store');
        Route::post('relations', [ProblemStructuringGridController::class, 'storeRelation'])->name('relations.store');
        Route::post('problems/{problem}/priority', [ProblemStructuringGridController::class, 'assessPriority'])->name('problems.priority');
        Route::post('problems/{problem}/evidences', [ProblemStructuringGridController::class, 'linkEvidence'])->name('problems.evidences.store');
        Route::post('snapshot', [ProblemStructuringGridController::class, 'generateSnapshot'])->name('snapshot');
    });

    // Diagnostic 360° - Outil 07 : Diagnostic Restitution Deck™
    Route::prefix('missions/{mission}/restitution-deck')->name('missions.restitution-deck.')->group(function () {
        Route::get('/', [DiagnosticRestitutionDeckController::class, 'show'])->name('show');
        Route::post('prepare', [DiagnosticRestitutionDeckController::class, 'prepare'])->name('prepare');
        Route::post('sections', [DiagnosticRestitutionDeckController::class, 'storeSection'])->name('sections.store');
        Route::put('sections/{section}', [DiagnosticRestitutionDeckController::class, 'updateSection'])->name('sections.update');
        Route::delete('sections/{section}', [DiagnosticRestitutionDeckController::class, 'destroySection'])->name('sections.destroy');
        Route::post('sections/{section}/validate', [DiagnosticRestitutionDeckController::class, 'validateSection'])->name('sections.validate');
        Route::post('sections/{section}/contents', [DiagnosticRestitutionDeckController::class, 'storeContent'])->name('sections.contents.store');
        Route::post('sections/{section}/import-result', [DiagnosticRestitutionDeckController::class, 'importResult'])->name('sections.import-result');
        Route::put('contents/{content}', [DiagnosticRestitutionDeckController::class, 'updateContent'])->name('contents.update');
        Route::post('snapshot', [DiagnosticRestitutionDeckController::class, 'generateSnapshot'])->name('snapshot');
    });

    // Diagnostic 360° - Outil 08 : Live Decision Capture™
    Route::prefix('missions/{mission}/decision-capture')->name('missions.decision-capture.')->group(function () {
        Route::get('/', [LiveDecisionCaptureController::class, 'show'])->name('show');
        Route::post('prepare', [LiveDecisionCaptureController::class, 'prepare'])->name('prepare');
        Route::post('exchanges', [LiveDecisionCaptureController::class, 'storeExchange'])->name('exchanges.store');
        Route::post('exchanges/{exchange}/points', [LiveDecisionCaptureController::class, 'storeDiscussionPoint'])->name('exchanges.points.store');
        Route::post('decisions', [LiveDecisionCaptureController::class, 'storeDecision'])->name('decisions.store');
        Route::put('decisions/{decision}', [LiveDecisionCaptureController::class, 'updateDecision'])->name('decisions.update');
        Route::post('decisions/{decision}/confirm', [LiveDecisionCaptureController::class, 'confirmDecision'])->name('decisions.confirm');
        Route::post('decisions/{decision}/revise', [LiveDecisionCaptureController::class, 'reviseDecision'])->name('decisions.revise');
        Route::post('decisions/{decision}/cancel', [LiveDecisionCaptureController::class, 'cancelDecision'])->name('decisions.cancel');
        Route::post('decisions/{decision}/commitments', [LiveDecisionCaptureController::class, 'storeCommitment'])->name('decisions.commitments.store');
        Route::put('commitments/{commitment}/status', [LiveDecisionCaptureController::class, 'updateCommitmentStatus'])->name('commitments.status.update');
        Route::post('snapshot', [LiveDecisionCaptureController::class, 'generateSnapshot'])->name('snapshot');
    });

    // Diagnostic 360° - Outil 09 : Diagnostic Archive Pack™
    Route::prefix('missions/{mission}/archive-pack')->name('missions.archive-pack.')->group(function () {
        Route::get('/', [DiagnosticArchivePackController::class, 'show'])->name('show');
        Route::post('collect', [DiagnosticArchivePackController::class, 'collect'])->name('collect');
        Route::post('items', [DiagnosticArchivePackController::class, 'storeItem'])->name('items.store');
        Route::put('items/{item}', [DiagnosticArchivePackController::class, 'updateItem'])->name('items.update');
        Route::post('items/{item}/versions', [DiagnosticArchivePackController::class, 'storeVersion'])->name('items.versions.store');
        Route::post('items/{item}/sources', [DiagnosticArchivePackController::class, 'storeSource'])->name('items.sources.store');
        Route::post('close', [DiagnosticArchivePackController::class, 'close'])->name('close');
        Route::post('snapshot', [DiagnosticArchivePackController::class, 'snapshot'])->name('snapshot');
        Route::get('search', [DiagnosticArchivePackController::class, 'search'])->name('search');
    });

    // Diagnostic 360° - Outil 10 : Next-Step Router™
    Route::prefix('missions/{mission}/next-step-router')->name('missions.next-step-router.')->group(function () {
        Route::get('/', [NextStepRouterController::class, 'show'])->name('show');
        Route::post('prepare', [NextStepRouterController::class, 'prepare'])->name('prepare');
        Route::post('recommendations', [NextStepRouterController::class, 'storeRecommendation'])->name('recommendations.store');
        Route::put('recommendations/{recommendation}', [NextStepRouterController::class, 'updateRecommendation'])->name('recommendations.update');
        Route::post('actions', [NextStepRouterController::class, 'storeAction'])->name('actions.store');
        Route::put('actions/{action}', [NextStepRouterController::class, 'updateAction'])->name('actions.update');
        Route::put('actions/{action}/status', [NextStepRouterController::class, 'updateActionStatus'])->name('actions.status.update');
        Route::post('snapshot', [NextStepRouterController::class, 'snapshot'])->name('snapshot');
    });

}); // Fin espace cabinet (no.client)

// Routes API JSON
Route::prefix('api')->name('api.')->group(function () {
    Route::apiResource('collections', CollectionController::class);
    Route::apiResource('analyses', AnalysisController::class)->only(['index', 'store', 'show']);
    Route::get('collections/{collection}/analyses', [AnalysisController::class, 'byCollection'])->name('collections.analyses');

    Route::apiResource('missions', MissionController::class);
    Route::apiResource('collaborators', CollaboratorController::class);
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    Route::prefix('missions/{mission}/framing-sheet')->name('framing-sheet.')->group(function () {
        Route::get('initial-data', [MissionFramingSheetController::class, 'initialData'])->name('initial-data');
        Route::get('/', [MissionFramingSheetController::class, 'show'])->name('show');
        Route::post('/', [MissionFramingSheetController::class, 'store'])->name('store');
        Route::put('/', [MissionFramingSheetController::class, 'update'])->name('update');
        Route::post('submit', [MissionFramingSheetController::class, 'submit'])->name('submit');
    });

    Route::prefix('missions/{mission}/scope-canvas')->name('scope-canvas.')->group(function () {
        Route::get('initial-data', [DiagnosticScopeCanvasController::class, 'initialData'])->name('initial-data');
        Route::get('/', [DiagnosticScopeCanvasController::class, 'show'])->name('show');
        Route::post('/', [DiagnosticScopeCanvasController::class, 'store'])->name('store');
        Route::put('/', [DiagnosticScopeCanvasController::class, 'update'])->name('update');
        Route::post('submit', [DiagnosticScopeCanvasController::class, 'submit'])->name('submit');
        Route::post('validate', [DiagnosticScopeCanvasController::class, 'validateScope'])->name('validate');
    });

    Route::prefix('missions/{mission}/intake-register')->name('intake-register.')->group(function () {
        Route::get('/', [DiagnosticIntakeRegisterController::class, 'show'])->name('show');
        Route::post('initialize', [DiagnosticIntakeRegisterController::class, 'initialize'])->name('initialize');
        Route::post('information', [DiagnosticIntakeRegisterController::class, 'storeInformation'])->name('information.store');
        Route::put('information/{information}', [DiagnosticIntakeRegisterController::class, 'updateInformation'])->name('information.update');
        Route::post('information/{information}/assign', [DiagnosticIntakeRegisterController::class, 'assign'])->name('information.assign');
        Route::post('information/{information}/status', [DiagnosticIntakeRegisterController::class, 'updateStatus'])->name('information.status');
        Route::post('received-items', [DiagnosticIntakeRegisterController::class, 'storeReceivedItem'])->name('received-items.store');
        Route::get('progress', [DiagnosticIntakeRegisterController::class, 'progress'])->name('progress');
    });

    Route::prefix('missions/{mission}/stakeholder-grid')->name('stakeholder-grid.')->group(function () {
        Route::get('/', [StakeholderInputGridController::class, 'show'])->name('show');
        Route::post('initialize', [StakeholderInputGridController::class, 'initialize'])->name('initialize');
        Route::post('stakeholders', [StakeholderInputGridController::class, 'storeStakeholder'])->name('stakeholders.store');
        Route::put('stakeholders/{stakeholder}', [StakeholderInputGridController::class, 'updateStakeholder'])->name('stakeholders.update');
        Route::post('expected-contributions', [StakeholderInputGridController::class, 'storeExpectedContribution'])->name('expected-contributions.store');
        Route::post('expected-contributions/{contribution}/assign', [StakeholderInputGridController::class, 'assignMobilization'])->name('expected-contributions.assign');
        Route::post('expected-contributions/{contribution}/status', [StakeholderInputGridController::class, 'updateMobilizationStatus'])->name('expected-contributions.status');
        Route::post('received-contributions', [StakeholderInputGridController::class, 'storeReceivedContribution'])->name('received-contributions.store');
        Route::get('progress', [StakeholderInputGridController::class, 'progress'])->name('progress');
    });

    Route::prefix('missions/{mission}/diagnostic-matrix')->name('diagnostic-matrix.')->group(function () {
        Route::get('/', [Diagnostic360MatrixController::class, 'show'])->name('show');
        Route::post('prepare', [Diagnostic360MatrixController::class, 'prepare'])->name('prepare');
        Route::post('assessments/{assessment}', [Diagnostic360MatrixController::class, 'updateAssessment'])->name('assessments.update');
        Route::post('assessments/{assessment}/evidence', [Diagnostic360MatrixController::class, 'linkEvidence'])->name('assessments.evidence');
        Route::post('assessments/{assessment}/findings', [Diagnostic360MatrixController::class, 'recordFinding'])->name('assessments.findings');
        Route::post('consolidate-axis', [Diagnostic360MatrixController::class, 'consolidateAxis'])->name('consolidate-axis');
        Route::post('consolidate-domain', [Diagnostic360MatrixController::class, 'consolidateDomain'])->name('consolidate-domain');
        Route::post('snapshot', [Diagnostic360MatrixController::class, 'generateSnapshot'])->name('snapshot');
    });

    Route::prefix('missions/{mission}/problem-grid')->name('problem-grid.')->group(function () {
        Route::get('/', [ProblemStructuringGridController::class, 'show'])->name('show');
        Route::post('prepare', [ProblemStructuringGridController::class, 'prepare'])->name('prepare');
        Route::post('problems', [ProblemStructuringGridController::class, 'storeProblem'])->name('problems.store');
        Route::put('problems/{problem}', [ProblemStructuringGridController::class, 'updateProblem'])->name('problems.update');
        Route::post('problems/{problem}/causes', [ProblemStructuringGridController::class, 'storeCause'])->name('problems.causes.store');
        Route::put('causes/{cause}/level', [ProblemStructuringGridController::class, 'updateCauseLevel'])->name('causes.level.update');
        Route::post('problems/{problem}/consequences', [ProblemStructuringGridController::class, 'storeConsequence'])->name('problems.consequences.store');
        Route::post('relations', [ProblemStructuringGridController::class, 'storeRelation'])->name('relations.store');
        Route::post('problems/{problem}/priority', [ProblemStructuringGridController::class, 'assessPriority'])->name('problems.priority');
        Route::post('problems/{problem}/evidences', [ProblemStructuringGridController::class, 'linkEvidence'])->name('problems.evidences.store');
        Route::post('snapshot', [ProblemStructuringGridController::class, 'generateSnapshot'])->name('snapshot');
    });

    Route::prefix('missions/{mission}/restitution-deck')->name('restitution-deck.')->group(function () {
        Route::get('/', [DiagnosticRestitutionDeckController::class, 'show'])->name('show');
        Route::post('prepare', [DiagnosticRestitutionDeckController::class, 'prepare'])->name('prepare');
        Route::post('sections', [DiagnosticRestitutionDeckController::class, 'storeSection'])->name('sections.store');
        Route::put('sections/{section}', [DiagnosticRestitutionDeckController::class, 'updateSection'])->name('sections.update');
        Route::delete('sections/{section}', [DiagnosticRestitutionDeckController::class, 'destroySection'])->name('sections.destroy');
        Route::post('sections/{section}/validate', [DiagnosticRestitutionDeckController::class, 'validateSection'])->name('sections.validate');
        Route::post('sections/{section}/contents', [DiagnosticRestitutionDeckController::class, 'storeContent'])->name('sections.contents.store');
        Route::post('sections/{section}/import-result', [DiagnosticRestitutionDeckController::class, 'importResult'])->name('sections.import-result');
        Route::put('contents/{content}', [DiagnosticRestitutionDeckController::class, 'updateContent'])->name('contents.update');
        Route::post('snapshot', [DiagnosticRestitutionDeckController::class, 'generateSnapshot'])->name('snapshot');
    });

    Route::prefix('missions/{mission}/decision-capture')->name('decision-capture.')->group(function () {
        Route::get('/', [LiveDecisionCaptureController::class, 'show'])->name('show');
        Route::post('prepare', [LiveDecisionCaptureController::class, 'prepare'])->name('prepare');
        Route::post('exchanges', [LiveDecisionCaptureController::class, 'storeExchange'])->name('exchanges.store');
        Route::post('exchanges/{exchange}/points', [LiveDecisionCaptureController::class, 'storeDiscussionPoint'])->name('exchanges.points.store');
        Route::post('decisions', [LiveDecisionCaptureController::class, 'storeDecision'])->name('decisions.store');
        Route::put('decisions/{decision}', [LiveDecisionCaptureController::class, 'updateDecision'])->name('decisions.update');
        Route::post('decisions/{decision}/confirm', [LiveDecisionCaptureController::class, 'confirmDecision'])->name('decisions.confirm');
        Route::post('decisions/{decision}/revise', [LiveDecisionCaptureController::class, 'reviseDecision'])->name('decisions.revise');
        Route::post('decisions/{decision}/cancel', [LiveDecisionCaptureController::class, 'cancelDecision'])->name('decisions.cancel');
        Route::post('decisions/{decision}/commitments', [LiveDecisionCaptureController::class, 'storeCommitment'])->name('decisions.commitments.store');
        Route::put('commitments/{commitment}/status', [LiveDecisionCaptureController::class, 'updateCommitmentStatus'])->name('commitments.status.update');
        Route::post('snapshot', [LiveDecisionCaptureController::class, 'generateSnapshot'])->name('snapshot');
    });

    Route::prefix('missions/{mission}/archive-pack')->name('archive-pack.')->group(function () {
        Route::get('/', [DiagnosticArchivePackController::class, 'show'])->name('show');
        Route::post('collect', [DiagnosticArchivePackController::class, 'collect'])->name('collect');
        Route::post('items', [DiagnosticArchivePackController::class, 'storeItem'])->name('items.store');
        Route::put('items/{item}', [DiagnosticArchivePackController::class, 'updateItem'])->name('items.update');
        Route::post('items/{item}/versions', [DiagnosticArchivePackController::class, 'storeVersion'])->name('items.versions.store');
        Route::post('items/{item}/sources', [DiagnosticArchivePackController::class, 'storeSource'])->name('items.sources.store');
        Route::post('close', [DiagnosticArchivePackController::class, 'close'])->name('close');
        Route::post('snapshot', [DiagnosticArchivePackController::class, 'snapshot'])->name('snapshot');
        Route::get('search', [DiagnosticArchivePackController::class, 'search'])->name('search');
    });

    Route::prefix('missions/{mission}/next-step-router')->name('next-step-router.')->group(function () {
        Route::get('/', [NextStepRouterController::class, 'show'])->name('show');
        Route::post('prepare', [NextStepRouterController::class, 'prepare'])->name('prepare');
        Route::post('recommendations', [NextStepRouterController::class, 'storeRecommendation'])->name('recommendations.store');
        Route::put('recommendations/{recommendation}', [NextStepRouterController::class, 'updateRecommendation'])->name('recommendations.update');
        Route::post('actions', [NextStepRouterController::class, 'storeAction'])->name('actions.store');
        Route::put('actions/{action}', [NextStepRouterController::class, 'updateAction'])->name('actions.update');
        Route::put('actions/{action}/status', [NextStepRouterController::class, 'updateActionStatus'])->name('actions.status.update');
        Route::post('snapshot', [NextStepRouterController::class, 'snapshot'])->name('snapshot');
    });
});
