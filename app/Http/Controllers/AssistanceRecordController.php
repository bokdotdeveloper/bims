<?php

namespace App\Http\Controllers;

use App\Models\AssistanceRecord;
use App\Models\Beneficiary;
use App\Models\BeneficiaryGroup;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AssistanceRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = AssistanceRecord::query()->with(['beneficiary', 'beneficiaryGroup', 'project']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->whereHas('beneficiary', function ($q3) use ($q) {
                    $q3->where('last_name', 'like', "%$q%")
                        ->orWhere('first_name', 'like', "%$q%");
                })->orWhereHas('beneficiaryGroup', function ($q3) use ($q) {
                    $q3->where('group_name', 'like', "%$q%");
                });
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('recipient_type')) {
            $query->where('recipient_type', $request->recipient_type);
        }

        return inertia('assistance-records.index', [
            'records' => $query->latest()->paginate($request->get('per_page', 20))->withQueryString(),
            'projects' => Project::select('id', 'project_name')->get(),
            // Individuals only if not linked to a group that is enrolled on any project (use group recipient otherwise)
            'beneficiaries' => Beneficiary::select('id', 'first_name', 'last_name', 'beneficiary_code')
                ->whereDoesntHave('groups', fn ($q) => $q->whereHas('projects'))
                ->orderBy('last_name')
                ->get(),
            'groups' => BeneficiaryGroup::select('id', 'group_name', 'group_type', 'total_members')->orderBy('group_name')->get(),
            'filters' => $request->only(['search', 'project_id', 'recipient_type']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_type' => 'required|in:individual,group',
            'beneficiary_id' => 'required_if:recipient_type,individual|nullable|exists:beneficiaries,id',
            'beneficiary_group_id' => 'required_if:recipient_type,group|nullable|exists:beneficiary_groups,id',
            'project_id' => 'nullable|exists:projects,id',
            'assistance_type' => 'required|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'date_released' => 'required|date',
            'released_by' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        // Nullify the unused recipient FK
        if ($validated['recipient_type'] === 'individual') {
            $validated['beneficiary_group_id'] = null;
            if (! empty($validated['beneficiary_id'])) {
                $b = Beneficiary::find($validated['beneficiary_id']);
                if ($b && $b->groups()->whereHas('projects')->exists()) {
                    throw ValidationException::withMessages([
                        'beneficiary_id' => 'This beneficiary is linked to a group enrolled in a project. Choose Beneficiary Group as the recipient type.',
                    ]);
                }
            }
        } else {
            $validated['beneficiary_id'] = null;
        }

        AssistanceRecord::create($validated);

        return back()->with('success', 'Assistance record created successfully.');
    }

    public function show(string $id)
    {
        $record = AssistanceRecord::with(['beneficiary', 'beneficiaryGroup', 'project'])->findOrFail($id);

        return response()->json($record);
    }

    public function update(Request $request, string $id)
    {
        $record = AssistanceRecord::findOrFail($id);

        $validated = $request->validate([
            'recipient_type' => 'required|in:individual,group',
            'beneficiary_id' => 'required_if:recipient_type,individual|nullable|exists:beneficiaries,id',
            'beneficiary_group_id' => 'required_if:recipient_type,group|nullable|exists:beneficiary_groups,id',
            'project_id' => 'nullable|exists:projects,id',
            'assistance_type' => 'required|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'date_released' => 'required|date',
            'released_by' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        if ($validated['recipient_type'] === 'individual') {
            $validated['beneficiary_group_id'] = null;
            if (! empty($validated['beneficiary_id'])) {
                $b = Beneficiary::find($validated['beneficiary_id']);
                if ($b && $b->groups()->whereHas('projects')->exists()) {
                    throw ValidationException::withMessages([
                        'beneficiary_id' => 'This beneficiary is linked to a group enrolled in a project. Choose Beneficiary Group as the recipient type.',
                    ]);
                }
            }
        } else {
            $validated['beneficiary_id'] = null;
        }

        $record->update($validated);

        return back()->with('success', 'Assistance record updated successfully.');
    }

    public function destroy(string $id)
    {
        $record = AssistanceRecord::findOrFail($id);
        $record->delete();

        return back()->with('success', 'Assistance record deleted successfully.');
    }
}
