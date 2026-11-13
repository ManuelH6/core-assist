<?php

namespace App\Modules\DiagnosticIntakeRegister\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\DiagnosticIntakeRegister\Models\RequiredInformation;
use App\Modules\DiagnosticIntakeRegister\Services\DiagnosticIntakeRegisterService;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticIntakeRegisterController extends Controller
{
    public function __construct(
        protected DiagnosticIntakeRegisterService $intakeService
    ) {}

    /**
     * Consulter le Diagnostic Intake Register d'une mission.
     */
    public function show(Request $request, Mission $mission): JsonResponse|View|RedirectResponse
    {
        $register = $mission->intakeRegister;

        // Si le registre n'a pas encore été initialisé, l'initialiser automatiquement à partir du DSC
        if (! $register) {
            $register = $this->intakeService->generateInitialRegister($mission, $request->user());
        }

        $progress = $this->intakeService->getCollectionProgress($register);
        $users = User::orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'register' => $register->load(['requiredInformation.assignedUser', 'requiredInformation.receivedItems', 'receivedItems']),
                    'progress' => $progress,
                ],
            ]);
        }

        $requiredItems = $register->requiredInformation()->with(['assignedUser', 'receivedItems'])->get();
        $receivedItems = $register->receivedItems()->with(['requiredInformation', 'receiver'])->latest()->get();

        return view('intake-registers.show', compact('mission', 'register', 'progress', 'requiredItems', 'receivedItems', 'users'));
    }

    /**
     * Initialiser ou regénérer la liste des besoins d'informations à partir du DSC.
     */
    public function initialize(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $register = $this->intakeService->generateInitialRegister($mission, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Registre de collecte initialisé avec succès à partir du périmètre validé.',
                'data' => $register,
            ]);
        }

        return redirect()->route('missions.intake-register.show', $mission)
            ->with('success', 'Besoins d’information pré-remplis à partir du périmètre du diagnostic.');
    }

    /**
     * Ajouter une nouvelle information recherchée au registre.
     */
    public function storeInformation(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $register = $mission->intakeRegister ?? $this->intakeService->generateInitialRegister($mission, $request->user());
        $info = $this->intakeService->addRequiredInformation($register, $request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Information recherchée ajoutée au registre.',
                'data' => $info,
            ], 201);
        }

        return redirect()->route('missions.intake-register.show', $mission)
            ->with('success', 'Besoin d’information ajouté avec succès.');
    }

    /**
     * Mettre à jour une information recherchée.
     */
    public function updateInformation(Request $request, Mission $mission, RequiredInformation $information): JsonResponse|RedirectResponse
    {
        $info = $this->intakeService->updateRequiredInformation($information, $request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Information mise à jour.',
                'data' => $info,
            ]);
        }

        return redirect()->route('missions.intake-register.show', $mission)
            ->with('success', 'Demande d’information mise à jour avec succès.');
    }

    /**
     * Affecter un collaborateur responsable de la collecte.
     */
    public function assign(Request $request, Mission $mission, RequiredInformation $information): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $info = $this->intakeService->assignResponsible($information, $validated['assigned_to'] ?? null);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Responsable de collecte affecté.',
                'data' => $info,
            ]);
        }

        return redirect()->route('missions.intake-register.show', $mission)
            ->with('success', 'Responsable de collecte affecté.');
    }

    /**
     * Mettre à jour le statut de collecte et de vérification.
     */
    public function updateStatus(Request $request, Mission $mission, RequiredInformation $information): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress,received,verified,unavailable'],
            'verification_status' => ['nullable', 'string', 'in:unverified,verified_valid,verified_incomplete,rejected'],
        ]);

        $info = $this->intakeService->updateCollectionStatus(
            $information,
            $validated['status'],
            $validated['verification_status'] ?? null,
            $request->user()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Statut de collecte mis à jour.',
                'data' => $info,
            ]);
        }

        return redirect()->route('missions.intake-register.show', $mission)
            ->with('success', 'Statut de collecte actualisé.');
    }

    /**
     * Enregistrer une pièce ou un document reçu et le lier aux besoins d'information.
     */
    public function storeReceivedItem(Request $request, Mission $mission): JsonResponse|RedirectResponse
    {
        $register = $mission->intakeRegister ?? $this->intakeService->generateInitialRegister($mission, $request->user());
        $item = $this->intakeService->registerReceivedItem($register, $request->all(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Document/élément reçu consigné avec succès.',
                'data' => $item,
            ], 201);
        }

        return redirect()->route('missions.intake-register.show', $mission)
            ->with('success', 'Document ou élément reçu consigné avec succès.');
    }

    /**
     * Obtenir l'avancement métrique de la collecte.
     */
    public function progress(Request $request, Mission $mission): JsonResponse
    {
        $register = $mission->intakeRegister;
        if (! $register) {
            return response()->json([
                'success' => false,
                'message' => 'Registre non initialisé.',
            ], 404);
        }

        $progress = $this->intakeService->getCollectionProgress($register);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }
}
