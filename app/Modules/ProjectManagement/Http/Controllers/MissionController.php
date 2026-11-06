<?php

namespace App\Modules\ProjectManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ProjectManagement\Http\Models\Collaborator;
use App\Modules\ProjectManagement\Http\Services\AlertService;
use App\Modules\ProjectManagement\Http\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionController extends Controller
{
    public function __construct(
        protected MissionService $missionService,
        protected AlertService $alertService
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

    public function create(): View
    {
        $collaborators = Collaborator::where('is_active', true)->orderBy('name')->get();
        return view('missions.create', compact('collaborators'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,in_progress,completed,on_hold'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'manager_id' => ['nullable', 'exists:collaborators,id'],
        ]);

        $mission = $this->missionService->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Mission créée avec succès.',
                'data' => $mission,
            ], 201);
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

        return view('missions.show', compact('mission', 'collaborators'));
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
}
