<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\BeneficiaryGroup;
use Illuminate\Http\Request;

class BeneficiaryGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = BeneficiaryGroup::query()->withCount('members');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('group_name', 'like', "%$q%")
                    ->orWhere('group_type', 'like', "%$q%");
            });
        }

        return inertia('beneficiary-groups.index', [
            'groups'  => $query->latest()->paginate($request->get('per_page', 20))->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_name'     => 'required|string|max:255',
            'group_type'     => 'nullable|string|max:100',
            'total_members'  => 'nullable|integer|min:0',
            'male_members'   => 'nullable|integer|min:0',
            'female_members' => 'nullable|integer|min:0',
            'date_organized' => 'nullable|date',
        ]);

        BeneficiaryGroup::create($validated);

        return back()->with('success', 'Beneficiary group created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $group = BeneficiaryGroup::findOrFail($id);

        $validated = $request->validate([
            'group_name'     => 'required|string|max:255',
            'group_type'     => 'nullable|string|max:100',
            'total_members'  => 'nullable|integer|min:0',
            'male_members'   => 'nullable|integer|min:0',
            'female_members' => 'nullable|integer|min:0',
            'date_organized' => 'nullable|date',
        ]);

        $group->update($validated);

        return back()->with('success', 'Beneficiary group updated successfully.');
    }

    public function destroy(string $id)
    {
        $group = BeneficiaryGroup::findOrFail($id);
        $group->delete();

        return back()->with('success', 'Beneficiary group deleted successfully.');
    }

    /** Return group members as JSON for the frontend drawer. */
    public function listMembers(string $id)
    {
        $group = BeneficiaryGroup::findOrFail($id);
        $members = $group->members()
            ->select('beneficiaries.id', 'first_name', 'last_name', 'beneficiary_code', 'barangay', 'sex')
            ->orderBy('last_name')
            ->get()
            ->map(fn ($b) => [
                'id'               => $b->id,
                'first_name'       => $b->first_name,
                'last_name'        => $b->last_name,
                'beneficiary_code' => $b->beneficiary_code,
                'barangay'         => $b->barangay,
                'sex'              => $b->sex,
                'date_joined'      => $b->pivot->date_joined,
            ]);

        return response()->json($members);
    }

    /** Add a beneficiary to this group. */
    public function addMember(Request $request, string $id)
    {
        $group = BeneficiaryGroup::findOrFail($id);

        $validated = $request->validate([
            'beneficiary_id' => 'required|exists:beneficiaries,id',
            'date_joined'    => 'nullable|date',
        ]);

        $group->members()->syncWithoutDetaching([
            $validated['beneficiary_id'] => [
                'date_joined' => $validated['date_joined'] ?? null,
            ],
        ]);

        return back()->with('success', 'Member added to group.');
    }

    /** Remove a beneficiary from this group. */
    public function removeMember(string $id, string $beneficiaryId)
    {
        $group = BeneficiaryGroup::findOrFail($id);
        $group->members()->detach($beneficiaryId);

        return back()->with('success', 'Member removed from group.');
    }

    /** Available beneficiaries (not yet in this group). */
    public function availableMembers(string $id)
    {
        $group = BeneficiaryGroup::findOrFail($id);
        $currentIds = $group->members()->pluck('beneficiaries.id');

        $beneficiaries = Beneficiary::select('id', 'first_name', 'last_name', 'beneficiary_code', 'sex')
            ->where('is_active', true)
            ->whereNotIn('id', $currentIds)
            ->orderBy('last_name')
            ->get();

        return response()->json($beneficiaries);
    }
}
