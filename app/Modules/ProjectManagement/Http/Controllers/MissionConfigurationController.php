<?php

namespace App\Modules\ProjectManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use App\Modules\ToolManagement\Services\MissionToolConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionConfigurationController extends Controller
{
    public function __construct(
        protected MissionToolConfigurationService $configService
    ) {}

    public function edit(Mission $mission): View
    {
        $mission->load(['framing.request.client', 'members.user', 'toolUsages.toolVersion.tool']);

        $toolMatrix = $this->configService->getMissionToolMatrix($mission);
        $actions = $this->configService->availableActions();
        $roles = $this->configService->configurableRoles();

        $cabinetUsers = User::where('role', '!=', 'client')
            ->orderBy('name')
            ->get();

        $activeMembers = $mission->members()
            ->where('status', 'active')
            ->get()
            ->keyBy('user_id');

        return view('missions.configure', compact(
            'mission',
            'toolMatrix',
            'actions',
            'roles',
            'cabinetUsers',
            'activeMembers'
        ));
    }

    public function update(Request $request, Mission $mission): RedirectResponse
    {
        $toolsInput = (array) $request->input('tools', []);
        $membersInput = (array) $request->input('members', []);

        $this->configService->saveConfiguration(
            $mission,
            $toolsInput,
            $membersInput,
            $request->user()
        );

        return redirect()
            ->route('missions.show', $mission)
            ->with('success', 'Configuration des outils, de l\'équipe et des autorisations enregistrée pour la mission.');
    }
}
