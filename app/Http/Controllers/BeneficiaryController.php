<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Beneficiary::query()->withCount(['assistanceRecords', 'groups']);

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('last_name', 'like', "%$q%")
                    ->orWhere('first_name', 'like', "%$q%")
                    ->orWhere('beneficiary_code', 'like', "%$q%");
            });
        }

        if ($request->filled('barangay')) {
            $query->where('barangay', $request->barangay);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return inertia('beneficiaries.index', [
            'beneficiaries' => $query->latest()->paginate($request->get('per_page', 20))->withQueryString(),
            'filters' => $request->only(['search', 'barangay', 'is_active']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'beneficiary_code'  => 'required|string|max:50|unique:beneficiaries',
            'beneficiary_type'  => 'nullable|string|max:100',
            'last_name'         => 'required|string|max:100',
            'first_name'        => 'required|string|max:100',
            'middle_name'       => 'nullable|string|max:100',
            'date_of_birth'     => 'required|date',
            'sex'               => 'required|in:Male,Female',
            'civil_status'      => 'required|string|max:50',
            'address'           => 'nullable|string|max:255',
            'barangay'          => 'nullable|string|max:100',
            'municipality'      => 'nullable|string|max:100',
            'province'          => 'nullable|string|max:100',
            'contact_number'    => 'nullable|string|max:20',
            'is_active'         => 'boolean',
        ]);

        $beneficiary = Beneficiary::create($validated);

        return back()->with('success', 'Beneficiary created successfully.');
    }

    /**
     * Return assistance records and group memberships as JSON for the details drawer.
     */
    public function details(string $id)
    {
        $beneficiary = Beneficiary::findOrFail($id);

        $assistance = $beneficiary->assistanceRecords()
            ->with('project:id,project_name')
            ->latest('date_released')
            ->get()
            ->map(fn ($r) => [
                'id'             => $r->id,
                'assistance_type'=> $r->assistance_type,
                'amount'         => $r->amount,
                'date_released'  => $r->date_released?->toDateString(),
                'project'        => $r->project?->project_name,
                'released_by'    => $r->released_by,
                'remarks'        => $r->remarks,
            ]);

        $groups = $beneficiary->groups()
            ->select('beneficiary_groups.id', 'group_name', 'group_type')
            ->get()
            ->map(fn ($g) => [
                'id'          => $g->id,
                'group_name'  => $g->group_name,
                'group_type'  => $g->group_type,
                'date_joined' => $g->pivot->date_joined,
            ]);

        return response()->json(compact('assistance', 'groups'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $beneficiary = Beneficiary::with(['projects', 'trainings', 'assistanceRecords'])->findOrFail($id);
        return inertia('beneficiaries.show', ['beneficiary' => $beneficiary]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $beneficiary = Beneficiary::findOrFail($id);

        $validated = $request->validate([
            'beneficiary_code'  => 'required|string|max:50|unique:beneficiaries,beneficiary_code,' . $id,
            'beneficiary_type'  => 'nullable|string|max:100',
            'last_name'         => 'required|string|max:100',
            'first_name'        => 'required|string|max:100',
            'middle_name'       => 'nullable|string|max:100',
            'date_of_birth'     => 'required|date',
            'sex'               => 'required|in:Male,Female',
            'civil_status'      => 'required|string|max:50',
            'address'           => 'nullable|string|max:255',
            'barangay'          => 'nullable|string|max:100',
            'municipality'      => 'nullable|string|max:100',
            'province'          => 'nullable|string|max:100',
            'contact_number'    => 'nullable|string|max:20',
            'is_active'         => 'boolean',
        ]);

        $beneficiary->update($validated);

        return back()->with('success', 'Beneficiary updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $beneficiary = Beneficiary::findOrFail($id);
        $beneficiary->delete();

        return back()->with('success', 'Beneficiary deleted successfully.');
    }
}
