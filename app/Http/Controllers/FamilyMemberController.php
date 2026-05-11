<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Beneficiary;
use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FamilyMemberController extends Controller
{
    /**
     * List all family members for a beneficiary.
     */
    public function index(string $beneficiaryId)
    {
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);

        $members = $beneficiary->familyMembers()
            ->with('linkedBeneficiary:id,beneficiary_code,first_name,last_name')
            ->get()
            ->map(fn ($m) => [
                'id'                     => $m->id,
                'relationship'           => $m->relationship,
                'last_name'              => $m->last_name,
                'first_name'             => $m->first_name,
                'middle_name'            => $m->middle_name,
                'date_of_birth'          => $m->date_of_birth?->toDateString(),
                'sex'                    => $m->sex,
                'civil_status'           => $m->civil_status,
                'occupation'             => $m->occupation,
                'educational_attainment' => $m->educational_attainment,
                'is_pwd'                 => $m->is_pwd,
                'is_senior'              => $m->is_senior,
                'remarks'                => $m->remarks,
                'linked_beneficiary_id'  => $m->linked_beneficiary_id,
                'linked_beneficiary'     => $m->linkedBeneficiary ? [
                    'id'               => $m->linkedBeneficiary->id,
                    'beneficiary_code' => $m->linkedBeneficiary->beneficiary_code,
                    'name'             => $m->linkedBeneficiary->last_name . ', ' . $m->linkedBeneficiary->first_name,
                ] : null,
            ]);

        return response()->json($members);
    }

    /**
     * Store a new family member.
     */
    public function store(Request $request, string $beneficiaryId)
    {
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);

        try {
            $validated = $request->validate([
                'relationship'           => 'required|string|max:50',
                'last_name'              => 'required|string|max:100',
                'first_name'             => 'required|string|max:100',
                'middle_name'            => 'nullable|string|max:100',
                'date_of_birth'          => 'nullable|date',
                'sex'                    => 'nullable|in:Male,Female',
                'civil_status'           => 'nullable|string|max:50',
                'occupation'             => 'nullable|string|max:100',
                'educational_attainment' => 'nullable|string|max:100',
                'is_pwd'                 => 'boolean',
                'is_senior'              => 'boolean',
                'linked_beneficiary_id'  => 'nullable|exists:beneficiaries,id',
                'remarks'                => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Please fill in all required fields correctly.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $member = $beneficiary->familyMembers()->create($validated);

        // ── Persist a notification if cross-match was recorded ──────────────
        if (!empty($validated['linked_beneficiary_id'])) {
            $linked = Beneficiary::find($validated['linked_beneficiary_id']);
            AppNotification::create([
                'type'    => 'warning',
                'title'   => 'Cross-match Recorded',
                'message' => sprintf(
                    '%s %s (%s) has a family member (%s) linked to beneficiary %s — %s %s. Monitor to prevent duplicate assistance.',
                    $beneficiary->first_name, $beneficiary->last_name, $beneficiary->beneficiary_code,
                    ucfirst($validated['relationship']),
                    $linked?->beneficiary_code ?? 'N/A',
                    $linked?->first_name ?? '', $linked?->last_name ?? ''
                ),
                'icon' => 'crossmatch',
                'meta' => [
                    'beneficiary_id'        => $beneficiary->id,
                    'beneficiary_code'      => $beneficiary->beneficiary_code,
                    'linked_beneficiary_id' => $linked?->id,
                    'linked_code'           => $linked?->beneficiary_code,
                    'family_member_id'      => $member->id,
                ],
            ]);
        }

        return response()->json(['message' => 'Family member added successfully.', 'id' => $member->id], 201);
    }

    /**
     * Update a family member.
     */
    public function update(Request $request, string $beneficiaryId, int $memberId)
    {
        $member = FamilyMember::where('beneficiary_id', $beneficiaryId)->findOrFail($memberId);

        try {
            $validated = $request->validate([
                'relationship'           => 'required|string|max:50',
                'last_name'              => 'required|string|max:100',
                'first_name'             => 'required|string|max:100',
                'middle_name'            => 'nullable|string|max:100',
                'date_of_birth'          => 'nullable|date',
                'sex'                    => 'nullable|in:Male,Female',
                'civil_status'           => 'nullable|string|max:50',
                'occupation'             => 'nullable|string|max:100',
                'educational_attainment' => 'nullable|string|max:100',
                'is_pwd'                 => 'boolean',
                'is_senior'              => 'boolean',
                'linked_beneficiary_id'  => 'nullable|exists:beneficiaries,id',
                'remarks'                => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Please fill in all required fields correctly.',
                'errors'  => $e->errors(),
            ], 422);
        }

        $member->update($validated);

        return response()->json(['message' => 'Family member updated successfully.']);
    }

    /**
     * Delete a family member.
     */
    public function destroy(string $beneficiaryId, int $memberId)
    {
        $member = FamilyMember::where('beneficiary_id', $beneficiaryId)->findOrFail($memberId);
        $member->delete();

        return response()->json(['message' => 'Family member removed successfully.']);
    }

    /**
     * Search existing beneficiaries to link as family member (cross-match check).
     */
    public function searchBeneficiaries(Request $request)
    {
        $q = $request->get('q', '');

        $results = Beneficiary::where(function ($query) use ($q) {
            $query->where('last_name', 'like', "%$q%")
                  ->orWhere('first_name', 'like', "%$q%")
                  ->orWhere('beneficiary_code', 'like', "%$q%");
        })
        ->select('id', 'beneficiary_code', 'first_name', 'last_name', 'middle_name',
                 'barangay', 'sex', 'civil_status', 'date_of_birth')
        ->limit(20)
        ->get()
        ->map(fn ($b) => [
            'id'               => $b->id,
            'beneficiary_code' => $b->beneficiary_code,
            'name'             => $b->last_name . ', ' . $b->first_name,
            'last_name'        => $b->last_name,
            'first_name'       => $b->first_name,
            'middle_name'      => $b->middle_name,
            'barangay'         => $b->barangay,
            'sex'              => $b->sex,
            'civil_status'     => $b->civil_status,
            'date_of_birth'    => $b->date_of_birth?->toDateString(),
        ]);

        return response()->json($results);
    }

    /**
     * Check similarity of manually typed name against registered beneficiaries.
     * Returns potential matches with a match score and matched fields.
     */
    public function checkSimilarity(Request $request)
    {
        $lastName  = strtolower(trim($request->get('last_name', '')));
        $firstName = strtolower(trim($request->get('first_name', '')));
        $dob       = $request->get('date_of_birth', '');
        $excludeId = $request->get('exclude_beneficiary_id', '');

        if (strlen($lastName) < 2 && strlen($firstName) < 2) {
            return response()->json([]);
        }

        $query = Beneficiary::query()
            ->where(function ($q) use ($lastName, $firstName) {
                if ($lastName) {
                    $q->orWhere('last_name', 'like', "%{$lastName}%");
                }
                if ($firstName) {
                    $q->orWhere('first_name', 'like', "%{$firstName}%");
                }
            })
            ->select('id', 'beneficiary_code', 'first_name', 'last_name', 'middle_name',
                     'date_of_birth', 'sex', 'civil_status', 'barangay', 'is_active');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $candidates = $query->limit(50)->get();

        $matches = $candidates->map(function ($b) use ($lastName, $firstName, $dob) {
            $matchedFields = [];
            $score = 0;

            // Last name match
            $bLast = strtolower($b->last_name);
            if ($bLast === $lastName) {
                $score += 40;
                $matchedFields[] = 'Last name (exact)';
            } elseif ($lastName && str_contains($bLast, $lastName)) {
                $score += 20;
                $matchedFields[] = 'Last name (partial)';
            }

            // First name match
            $bFirst = strtolower($b->first_name);
            if ($bFirst === $firstName) {
                $score += 40;
                $matchedFields[] = 'First name (exact)';
            } elseif ($firstName && str_contains($bFirst, $firstName)) {
                $score += 20;
                $matchedFields[] = 'First name (partial)';
            }

            // Date of birth
            if ($dob && $b->date_of_birth && $b->date_of_birth->toDateString() === $dob) {
                $score += 30;
                $matchedFields[] = 'Date of birth';
            }

            return [
                'id'               => $b->id,
                'beneficiary_code' => $b->beneficiary_code,
                'name'             => $b->last_name . ', ' . $b->first_name . ($b->middle_name ? ' ' . $b->middle_name : ''),
                'date_of_birth'    => $b->date_of_birth?->toDateString(),
                'sex'              => $b->sex,
                'civil_status'     => $b->civil_status,
                'barangay'         => $b->barangay,
                'is_active'        => $b->is_active,
                'score'            => $score,
                'matched_fields'   => $matchedFields,
            ];
        })
        ->filter(fn ($b) => $b['score'] >= 40)   // only meaningful matches
        ->sortByDesc('score')
        ->values();

        return response()->json($matches);
    }

    /**
     * Check if a new beneficiary being registered matches any existing family member records.
     * Useful to warn staff before saving a new beneficiary who may already be listed as a family member.
     */
    public function checkFamilyMemberMatch(Request $request)
    {
        $lastName  = strtolower(trim($request->get('last_name', '')));
        $firstName = strtolower(trim($request->get('first_name', '')));
        $dob       = $request->get('date_of_birth', '');

        if (strlen($lastName) < 2 && strlen($firstName) < 2) {
            return response()->json([]);
        }

        $query = FamilyMember::query()
            ->with('beneficiary:id,beneficiary_code,first_name,last_name,barangay')
            ->where(function ($q) use ($lastName, $firstName) {
                if ($lastName) {
                    $q->orWhere('last_name', 'like', "%{$lastName}%");
                }
                if ($firstName) {
                    $q->orWhere('first_name', 'like', "%{$firstName}%");
                }
            });

        $candidates = $query->limit(50)->get();

        $matches = $candidates->map(function ($m) use ($lastName, $firstName, $dob) {
            $matchedFields = [];
            $score = 0;

            $mLast = strtolower($m->last_name);
            if ($mLast === $lastName) {
                $score += 40; $matchedFields[] = 'Last name (exact)';
            } elseif ($lastName && str_contains($mLast, $lastName)) {
                $score += 20; $matchedFields[] = 'Last name (partial)';
            }

            $mFirst = strtolower($m->first_name);
            if ($mFirst === $firstName) {
                $score += 40; $matchedFields[] = 'First name (exact)';
            } elseif ($firstName && str_contains($mFirst, $firstName)) {
                $score += 20; $matchedFields[] = 'First name (partial)';
            }

            if ($dob && $m->date_of_birth && $m->date_of_birth->toDateString() === $dob) {
                $score += 30; $matchedFields[] = 'Date of birth';
            }

            return [
                'family_member_id'  => $m->id,
                'name'              => $m->last_name . ', ' . $m->first_name . ($m->middle_name ? ' ' . $m->middle_name : ''),
                'relationship'      => $m->relationship,
                'date_of_birth'     => $m->date_of_birth?->toDateString(),
                'sex'               => $m->sex,
                'owner_beneficiary' => $m->beneficiary ? [
                    'id'               => $m->beneficiary->id,
                    'beneficiary_code' => $m->beneficiary->beneficiary_code,
                    'name'             => $m->beneficiary->last_name . ', ' . $m->beneficiary->first_name,
                    'barangay'         => $m->beneficiary->barangay,
                ] : null,
                'score'          => $score,
                'matched_fields' => $matchedFields,
            ];
        })
        ->filter(fn ($m) => $m['score'] >= 40)
        ->sortByDesc('score')
        ->values();

        return response()->json($matches);
    }
}
