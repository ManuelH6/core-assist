<?php

namespace App\Modules\ProjectManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ProjectManagement\Http\Models\Task;
use App\Modules\ProjectManagement\Http\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'mission_id' => ['required', 'exists:missions,id'],
            'collaborator_id' => ['nullable', 'exists:collaborators,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'weekly_hours' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = $this->taskService->create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tâche ajoutée.',
                'data' => $task,
            ], 201);
        }

        return redirect()->route('missions.show', $task->mission_id)
            ->with('success', 'Intervention / Tâche ajoutée avec succès.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:todo,doing,in_progress,done,cancelled'],
        ]);

        $this->taskService->changeStatus($task, $validated['status']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour.',
                'data' => $task,
            ]);
        }

        return redirect()->back()->with('success', 'Statut de la tâche mis à jour.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $missionId = $task->mission_id;
        $this->taskService->delete($task);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tâche supprimée.',
            ]);
        }

        return redirect()->route('missions.show', $missionId)
            ->with('success', 'Tâche supprimée.');
    }
}
