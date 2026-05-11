<?php

namespace App\Http\Controllers;

use App\Exports\AssistanceRecordsExport;
use App\Exports\BeneficiariesExport;
use App\Exports\ProjectsExport;
use App\Exports\TrainingsExport;
use App\Models\AssistanceRecord;
use App\Models\Beneficiary;
use App\Models\Project;
use App\Models\Training;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /* ─── Beneficiaries ──────────────────────────────────────── */

    public function beneficiariesPdf(Request $request)
    {
        $query = Beneficiary::query();
        $this->applyBeneficiaryFilters($query, $request);
        $beneficiaries = $query->orderBy('last_name')->get();

        $data = [
            'beneficiaries' => $beneficiaries,
            'total'         => $beneficiaries->count(),
            'active'        => $beneficiaries->where('is_active', true)->count(),
            'inactive'      => $beneficiaries->where('is_active', false)->count(),
            'male'          => $beneficiaries->where('sex', 'Male')->count(),
            'female'        => $beneficiaries->where('sex', 'Female')->count(),
            'filters'       => $this->filterSummary($request, ['search', 'is_active']),
        ];

        $pdf = Pdf::loadView('reports.beneficiaries', $data)->setPaper('a4', 'landscape');
        return $pdf->download('beneficiaries-' . now()->format('Ymd') . '.pdf');
    }

    public function beneficiariesExcel(Request $request)
    {
        return Excel::download(new BeneficiariesExport($request), 'beneficiaries-' . now()->format('Ymd') . '.xlsx');
    }

    private function applyBeneficiaryFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($q2) => $q2->where('last_name', 'like', "%$q%")
                ->orWhere('first_name', 'like', "%$q%")
                ->orWhere('beneficiary_code', 'like', "%$q%"));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
    }

    /* ─── Projects ───────────────────────────────────────────── */

    public function projectsPdf(Request $request)
    {
        $query = Project::query()->withCount('beneficiaries');
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($q2) => $q2->where('project_name', 'like', "%$q%")->orWhere('project_code', 'like', "%$q%"));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        $projects = $query->latest()->get();

        $pdf = Pdf::loadView('reports.projects', [
            'projects' => $projects,
            'total'    => $projects->count(),
            'filters'  => $this->filterSummary($request, ['search', 'is_active']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('projects-' . now()->format('Ymd') . '.pdf');
    }

    public function projectsExcel(Request $request)
    {
        return Excel::download(new ProjectsExport($request), 'projects-' . now()->format('Ymd') . '.xlsx');
    }

    /* ─── Trainings ──────────────────────────────────────────── */

    public function trainingsPdf(Request $request)
    {
        $query = Training::query()->with('project')->withCount('beneficiaries');
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($q2) => $q2->where('training_tile', 'like', "%$q%")->orWhere('facilitator', 'like', "%$q%"));
        }
        if ($request->filled('project_id')) $query->where('project_id', $request->project_id);

        $trainings = $query->latest()->get();

        $pdf = Pdf::loadView('reports.trainings', [
            'trainings' => $trainings,
            'total'     => $trainings->count(),
            'filters'   => $this->filterSummary($request, ['search', 'project_id']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('trainings-' . now()->format('Ymd') . '.pdf');
    }

    public function trainingsExcel(Request $request)
    {
        return Excel::download(new TrainingsExport($request), 'trainings-' . now()->format('Ymd') . '.xlsx');
    }

    /* ─── Assistance Records ─────────────────────────────────── */

    public function assistancePdf(Request $request)
    {
        $query = AssistanceRecord::query()->with(['beneficiary', 'beneficiaryGroup', 'project']);
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($q2) => $q2
                ->whereHas('beneficiary', fn($q3) => $q3->where('last_name', 'like', "%$q%")->orWhere('first_name', 'like', "%$q%"))
                ->orWhereHas('beneficiaryGroup', fn($q3) => $q3->where('group_name', 'like', "%$q%")));
        }
        if ($request->filled('project_id'))    $query->where('project_id', $request->project_id);
        if ($request->filled('recipient_type')) $query->where('recipient_type', $request->recipient_type);

        $records = $query->latest()->get();

        $pdf = Pdf::loadView('reports.assistance-records', [
            'records'         => $records,
            'total'           => $records->count(),
            'totalAmount'     => $records->sum('amount'),
            'individualCount' => $records->where('recipient_type', 'individual')->count(),
            'groupCount'      => $records->where('recipient_type', 'group')->count(),
            'filters'         => $this->filterSummary($request, ['search', 'project_id', 'recipient_type']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('assistance-records-' . now()->format('Ymd') . '.pdf');
    }

    public function assistanceExcel(Request $request)
    {
        return Excel::download(new AssistanceRecordsExport($request), 'assistance-records-' . now()->format('Ymd') . '.xlsx');
    }

    /* ─── Helpers ────────────────────────────────────────────── */

    private function filterSummary(Request $request, array $keys): string
    {
        $parts = [];
        foreach ($keys as $key) {
            if ($request->filled($key)) {
                $parts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $request->input($key);
            }
        }
        return implode(', ', $parts);
    }
}

