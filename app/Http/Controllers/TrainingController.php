<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\Project;
use App\Models\Training;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $query = Training::query()->with('project')->withCount('beneficiaries');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('training_tile', 'like', "%$q%")
                    ->orWhere('facilitator', 'like', "%$q%");
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        return inertia('trainings.index', [
            'trainings' => $query->latest()->paginate($request->get('per_page', 20))->withQueryString(),
            'projects' => Project::select('id', 'project_name')->get(),
            'filters' => $request->only(['search', 'project_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'training_tile' => 'required|string|max:255',
            'training_type' => 'nullable|string|max:100',
            'facilitator' => 'nullable|string|max:255',
            'venue' => 'nullable|string|max:255',
            'date_conducted' => 'required|date',
            'duration_hours' => 'nullable|numeric|min:0',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        Training::create($validated);

        return back()->with('success', 'Training created successfully.');
    }

    public function show(string $id)
    {
        $training = Training::with(['project', 'beneficiaries'])->findOrFail($id);

        return inertia('trainings.show', ['training' => $training]);
    }

    public function update(Request $request, string $id)
    {
        $training = Training::findOrFail($id);

        $validated = $request->validate([
            'training_tile' => 'required|string|max:255',
            'training_type' => 'nullable|string|max:100',
            'facilitator' => 'nullable|string|max:255',
            'venue' => 'nullable|string|max:255',
            'date_conducted' => 'required|date',
            'duration_hours' => 'nullable|numeric|min:0',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $training->update($validated);

        return back()->with('success', 'Training updated successfully.');
    }

    public function destroy(string $id)
    {
        $training = Training::findOrFail($id);
        $training->delete();

        return back()->with('success', 'Training deleted successfully.');
    }

    /** Return participants as JSON for the frontend drawer. */
    public function listParticipants(string $id)
    {
        $training = Training::findOrFail($id);
        $participants = $training->beneficiaries()
            ->select('beneficiaries.id', 'first_name', 'last_name', 'beneficiary_code', 'barangay')
            ->orderBy('last_name')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'first_name' => $b->first_name,
                'last_name' => $b->last_name,
                'beneficiary_code' => $b->beneficiary_code,
                'barangay' => $b->barangay,
                'date_attended' => $b->pivot->date_attended,
                'completion_status' => $b->pivot->completion_status,
            ]);

        return response()->json($participants);
    }

    /** Add a participant to a training. */
    public function addParticipant(Request $request, string $id)
    {
        $training = Training::findOrFail($id);

        $validated = $request->validate([
            'beneficiary_id' => 'required|exists:beneficiaries,id',
            'date_attended' => 'nullable|date',
            'completion_status' => 'required|in:Completed,Incomplete,Dropped',
        ]);

        $training->beneficiaries()->syncWithoutDetaching([
            $validated['beneficiary_id'] => [
                'date_attended' => $validated['date_attended'] ?? null,
                'completion_status' => $validated['completion_status'],
            ],
        ]);

        AuditLogger::recordBeneficiaryMemberAttached($training, $validated['beneficiary_id'], [
            'date_attended' => $validated['date_attended'] ?? null,
            'completion_status' => $validated['completion_status'],
        ]);

        return back()->with('success', 'Participant added.');
    }

    /** Remove a participant from a training. */
    public function removeParticipant(string $id, string $beneficiaryId)
    {
        $training = Training::findOrFail($id);
        $training->beneficiaries()->detach($beneficiaryId);

        AuditLogger::recordBeneficiaryMemberDetached($training, $beneficiaryId);

        return back()->with('success', 'Participant removed.');
    }

    /** Available beneficiaries (not yet in this training). */
    public function availableParticipants(string $id)
    {
        $training = Training::findOrFail($id);
        $enrolledIds = $training->beneficiaries()->pluck('beneficiaries.id');

        $beneficiaries = Beneficiary::select('id', 'first_name', 'last_name', 'beneficiary_code')
            ->where('is_active', true)
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('last_name')
            ->get();

        return response()->json($beneficiaries);
    }
}
