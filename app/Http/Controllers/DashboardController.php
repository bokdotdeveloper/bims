<?php

namespace App\Http\Controllers;

use App\Models\AssistanceRecord;
use App\Models\Beneficiary;
use App\Models\BeneficiaryGroup;
use App\Models\Project;
use App\Models\Training;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Summary cards
        $totalBeneficiaries = Beneficiary::count();
        $activeBeneficiaries = Beneficiary::where('is_active', true)->count();
        $totalProjects = Project::count();
        $activeProjects = Project::query()->currentlyActiveByDates()->count();
        $totalTrainings = Training::count();
        $totalAssistance = AssistanceRecord::count();
        $totalAmountReleased = AssistanceRecord::sum('amount');
        $totalGroups = BeneficiaryGroup::count();

        // Assistance by type (top 8)
        $assistanceByType = AssistanceRecord::select('assistance_type', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('assistance_type')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // Monthly assistance amount (last 12 months) — strftime is SQLite-only; MySQL uses DATE_FORMAT.
        $monthKeyExpr = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', date_released)",
            'pgsql' => "to_char(date_released, 'YYYY-MM')",
            default => "DATE_FORMAT(date_released, '%Y-%m')",
        };

        $monthlyAssistance = AssistanceRecord::select(
            DB::raw("{$monthKeyExpr} as month"),
            DB::raw('sum(amount) as total')
        )
            ->whereNotNull('date_released')
            ->where('date_released', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->groupBy(DB::raw($monthKeyExpr))
            ->orderBy('month')
            ->get();

        // Beneficiaries by sex
        $beneficiariesBySex = Beneficiary::select('sex', DB::raw('count(*) as count'))
            ->groupBy('sex')
            ->get();

        // Beneficiaries by civil status
        $beneficiariesByCivilStatus = Beneficiary::select('civil_status', DB::raw('count(*) as count'))
            ->groupBy('civil_status')
            ->get();

        // Top 5 barangays by beneficiary count
        $topBarangays = Beneficiary::select('barangay', DB::raw('count(*) as count'))
            ->whereNotNull('barangay')
            ->groupBy('barangay')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Recent assistance records (last 5)
        $recentAssistance = AssistanceRecord::with(['beneficiary', 'beneficiaryGroup', 'project'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'recipient' => $r->recipient_type === 'individual'
                    ? ($r->beneficiary ? $r->beneficiary->last_name.', '.$r->beneficiary->first_name : '—')
                    : ($r->beneficiaryGroup?->group_name ?? '—'),
                'type' => $r->recipient_type,
                'assistance_type' => $r->assistance_type,
                'amount' => $r->amount,
                'date_released' => $r->date_released?->toDateString(),
                'project' => $r->project?->project_name,
            ]);

        return inertia('Dashboard', [
            'stats' => [
                'totalBeneficiaries' => $totalBeneficiaries,
                'activeBeneficiaries' => $activeBeneficiaries,
                'totalProjects' => $totalProjects,
                'activeProjects' => $activeProjects,
                'totalTrainings' => $totalTrainings,
                'totalAssistance' => $totalAssistance,
                'totalAmountReleased' => (float) $totalAmountReleased,
                'totalGroups' => $totalGroups,
            ],
            'charts' => [
                'assistanceByType' => $assistanceByType,
                'monthlyAssistance' => $monthlyAssistance,
                'beneficiariesBySex' => $beneficiariesBySex,
                'beneficiariesByCivilStatus' => $beneficiariesByCivilStatus,
                'topBarangays' => $topBarangays,
            ],
            'recentAssistance' => $recentAssistance,
        ]);
    }
}
