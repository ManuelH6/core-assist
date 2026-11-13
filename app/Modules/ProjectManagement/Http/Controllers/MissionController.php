<?php

namespace App\Modules\ProjectManagement\Http\Controllers;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Modules\ClientRelationship\Http\Models\Framing;
use App\Modules\ProjectManagement\Http\Models\Collaborator;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ProjectManagement\Http\Services\AlertService;
use App\Modules\ProjectManagement\Http\Services\MissionService;
use App\Modules\ToolManagement\Services\MissionToolConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionController extends Controller
{
    public function __construct(
        protected MissionService $missionService,
        protected AlertService $alertService,
        protected MissionToolConfigurationService $toolConfigService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $allMissions = $this->missionService->getAll();
        $summary = $this->missionService->getDashboardSummary();
        $alertsSummary = $this->alertService->getAlertsSummary();
        $activeAlerts = $this->alertService->getAllActiveAlerts();

        $filter = $request->query('filter');

        $missions = match ($filter) {
            'alerts' => $allMissions->filter(fn ($m) => $m->isOverdue() || $m->isAtRisk()),
            'overdue' => $allMissions->filter(fn ($m) => $m->isOverdue()),
            'in_progress' => $allMissions->where('status', 'in_progress'),
            'completed' => $allMissions->where('status', 'completed'),
            default => $allMissions,
        };

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $missions,
                'summary' => $summary,
                'alerts_summary' => $alertsSummary,
                'active_alerts' => $activeAlerts,
            ]);
        }

        return view('missions.index', compact('missions', 'summary', 'alertsSummary', 'activeAlerts', 'filter'));
    }

    public function create(Request $request): View
    {
        $collaborators = Collaborator::where('is_active', true)->orderBy('name')->get();
        $framing = null;

        if ($request->filled('framing_id')) {
            $framing = Framing::with(['request.client', 'request.initialUnderstanding', 'latestVersion'])
                ->find($request->integer('framing_id'));
        }

        return view('missions.create', compact('collaborators', 'framing'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'framing_id' => ['nullable', 'exists:framings,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,in_progress,completed,on_hold'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'manager_id' => ['nullable', 'exists:collaborators,id'],
        ]);

        if (empty($validated['reference'])) {
            $validated['reference'] = $this->generateMissionReference();
        }

        if ($request->user()) {
            $validated['created_by'] = $request->user()->id;
        }

        $mission = $this->missionService->create($validated);

        if (! empty($validated['framing_id'])) {
            $framing = Framing::with('request')->find($validated['framing_id']);
            if ($framing && $framing->request && $framing->request->status !== RequestStatus::Converted) {
                $framing->request->update(['status' => RequestStatus::Converted]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mission créée avec succès.',
                'data' => $mission,
            ], 201);
        }

        if ($request->user()?->canConfigureMissions()) {
            return redirect()->route('missions.configuration.edit', $mission)
                ->with('success', 'Mission créée. Configurez maintenant les outils, l\'équipe et les autorisations de cette mission.');
        }

        return redirect()->route('missions.show', $mission)
            ->with('success', 'Mission créée avec succès.');
    }

    public function show(Request $request, int $id): View|JsonResponse
    {
        $mission = $this->missionService->findById($id);
        $collaborators = Collaborator::where('is_active', true)->orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $mission,
            ]);
        }

        $user = $request->user();
        $toolPermissions = [];
        $toolCodes = [
            'D360-MFS', 'D360-DSC', 'D360-DIR', 'D360-SIG', 'D360-D360',
            'D360-PSG', 'D360-DRD', 'D360-LDC', 'D360-DAP', 'D360-NSR',
        ];
        foreach ($toolCodes as $code) {
            $toolPermissions[$code] = $this->toolConfigService->getEffectivePermissions($mission, $user, $code);
        }

        $hasToolConfiguration = $mission->toolUsages->isNotEmpty();

        return view('missions.show', compact(
            'mission',
            'collaborators',
            'toolPermissions',
            'hasToolConfiguration'
        ));
    }

    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $mission = $this->missionService->findById($id);
        $this->missionService->delete($mission);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mission supprimée.',
            ]);
        }

        return redirect()->route('missions.index')
            ->with('success', 'Mission supprimée avec succès.');
    }

    private function generateMissionReference(): string
    {
        $year = now()->format('Y');
        $sequence = Mission::whereYear('created_at', $year)->count() + 1;

        do {
            $reference = sprintf('MIS-%s-%03d', $year, $sequence);
            $sequence++;
        } while (Mission::where('reference', $reference)->exists());

        return $reference;
    }
}
