<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\BeneficiaryGroup;
use App\Models\Project;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query()->withCount(['beneficiaries', 'beneficiaryGroups']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('project_name', 'like', "%$q%")
                    ->orWhere('project_code', 'like', "%$q%");
            });
        }

        if ($request->filled('is_active')) {
            if ($request->boolean('is_active')) {
                $query->currentlyActiveByDates();
            } else {
                $query->notCurrentlyActiveByDates();
            }
        }

        return inertia('projects.index', [
            'projects' => $query->latest()->paginate($request->get('per_page', 20))->withQueryString(),
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'project_code' => 'required|string|max:50|unique:projects',
            'description' => 'nullable|string',
            'date_started' => 'required|date',
            'date_ended' => 'nullable|date|after_or_equal:date_started',
            'fund_source' => 'nullable|string|max:255',
        ]);

        Project::create($validated);

        return back()->with('success', 'Project created successfully.');
    }

    public function show(string $id)
    {
        $project = Project::with('beneficiaries')->findOrFail($id);

        return inertia('projects.show', ['project' => $project]);
    }

    public function update(Request $request, string $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'project_code' => 'required|string|max:50|unique:projects,project_code,'.$id,
            'description' => 'nullable|string',
            'date_started' => 'required|date',
            'date_ended' => 'nullable|date|after_or_equal:date_started',
            'fund_source' => 'nullable|string|max:255',
        ]);

        $project->update($validated);

        return back()->with('success', 'Project updated successfully.');
    }

    public function destroy(string $id)
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return back()->with('success', 'Project deleted successfully.');
    }

    /** Return enrolled beneficiaries as JSON (used by the frontend drawer). */
    public function listBeneficiaries(string $id)
    {
        $project = Project::findOrFail($id);
        $members = $project->beneficiaries()
            ->select('beneficiaries.id', 'first_name', 'last_name', 'beneficiary_code', 'barangay')
            ->orderBy('last_name')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'first_name' => $b->first_name,
                'last_name' => $b->last_name,
                'beneficiary_code' => $b->beneficiary_code,
                'barangay' => $b->barangay,
                'date_enrolled' => $b->pivot->date_enrolled,
                'status' => $b->pivot->status,
            ]);

        return response()->json($members);
    }

    /** Enroll a beneficiary into a project. */
    public function enrollBeneficiary(Request $request, string $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'beneficiary_id' => 'required|exists:beneficiaries,id',
            'date_enrolled' => 'required|date',
            'status' => 'required|in:Active,Completed,Dropped,Transferred',
            'remarks' => 'nullable|string',
        ]);

        $beneficiary = Beneficiary::findOrFail($validated['beneficiary_id']);
        if ($beneficiary->groups()->whereHas('projects', fn ($q) => $q->where('projects.id', $project->id))->exists()) {
            throw ValidationException::withMessages([
                'beneficiary_id' => 'This beneficiary belongs to a beneficiary group already enrolled in this project. Manage enrollment via the group or remove the group first.',
            ]);
        }

        $project->beneficiaries()->syncWithoutDetaching([
            $validated['beneficiary_id'] => [
                'date_enrolled' => $validated['date_enrolled'],
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
            ],
        ]);

        AuditLogger::recordBeneficiaryMemberAttached($project, $validated['beneficiary_id'], [
            'date_enrolled' => $validated['date_enrolled'],
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return back()->with('success', 'Beneficiary enrolled.');
    }

    /** Remove a beneficiary from a project. */
    public function removeBeneficiary(string $id, string $beneficiaryId)
    {
        $project = Project::findOrFail($id);
        $project->beneficiaries()->detach($beneficiaryId);

        AuditLogger::recordBeneficiaryMemberDetached($project, $beneficiaryId);

        return back()->with('success', 'Beneficiary removed from project.');
    }

    /** Available beneficiaries (not yet enrolled in this project). */
    public function availableBeneficiaries(string $id)
    {
        $project = Project::findOrFail($id);
        $enrolledIds = $project->beneficiaries()->pluck('beneficiaries.id');

        $beneficiaries = Beneficiary::select('id', 'first_name', 'last_name', 'beneficiary_code')
            ->where('is_active', true)
            ->whereNotIn('id', $enrolledIds)
            ->whereDoesntHave('groups', function ($q) use ($project): void {
                $q->whereHas('projects', fn ($q2) => $q2->where('projects.id', $project->id));
            })
            ->orderBy('last_name')
            ->get();

        return response()->json($beneficiaries);
    }

    /** Return enrolled beneficiary groups as JSON (drawer). */
    public function listGroups(string $id)
    {
        $project = Project::findOrFail($id);
        $groups = $project->beneficiaryGroups()
            ->select('beneficiary_groups.id', 'group_name', 'group_type', 'total_members')
            ->orderBy('group_name')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'group_name' => $g->group_name,
                'group_type' => $g->group_type,
                'total_members' => $g->total_members,
                'date_enrolled' => $g->pivot->date_enrolled,
                'status' => $g->pivot->status,
            ]);

        return response()->json($groups);
    }

    public function enrollGroup(Request $request, string $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'beneficiary_group_id' => 'required|exists:beneficiary_groups,id',
            'date_enrolled' => 'required|date',
            'status' => 'required|in:Active,Completed,Dropped,Transferred',
            'remarks' => 'nullable|string',
        ]);

        $project->beneficiaryGroups()->syncWithoutDetaching([
            $validated['beneficiary_group_id'] => [
                'date_enrolled' => $validated['date_enrolled'],
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
            ],
        ]);

        AuditLogger::record($project, 'member_added', [], [
            'beneficiary_group_id' => (string) $validated['beneficiary_group_id'],
            'enrollment_type' => 'beneficiary_group',
            'date_enrolled' => $validated['date_enrolled'],
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return back()->with('success', 'Beneficiary group enrolled.');
    }

    public function removeGroup(string $id, BeneficiaryGroup $group)
    {
        $project = Project::findOrFail($id);
        $project->beneficiaryGroups()->detach($group->getKey());

        AuditLogger::record($project, 'member_removed', [
            'beneficiary_group_id' => (string) $group->getKey(),
            'enrollment_type' => 'beneficiary_group',
        ], []);

        return back()->with('success', 'Beneficiary group removed from project.');
    }

    /** Groups not yet enrolled on this project. */
    public function availableGroups(string $id)
    {
        $project = Project::findOrFail($id);

        $groups = BeneficiaryGroup::select('id', 'group_name', 'group_type', 'total_members')
            ->whereDoesntHave('projects', fn ($q) => $q->where('projects.id', $project->id))
            ->orderBy('group_name')
            ->get();

        return response()->json($groups);
    }
}
