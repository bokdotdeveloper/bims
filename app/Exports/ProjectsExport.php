<?php

namespace App\Exports;

use App\Models\Project;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectsExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(protected Request $request) {}

    public function collection()
    {
        $query = Project::query()->withCount('beneficiaries');
        if ($this->request->filled('search')) {
            $q = $this->request->search;
            $query->where(fn($q2) => $q2->where('project_name', 'like', "%$q%")->orWhere('project_code', 'like', "%$q%"));
        }
        if ($this->request->filled('is_active')) {
            $query->where('is_active', $this->request->boolean('is_active'));
        }

        return $query->latest()->get()->map(fn ($p, $i) => [
            $i + 1,
            $p->project_code,
            $p->project_name,
            $p->fund_source,
            $p->date_started?->format('Y-m-d'),
            $p->date_ended?->format('Y-m-d'),
            $p->beneficiaries_count,
            $p->is_active ? 'Active' : 'Inactive',
            $p->description,
        ]);
    }

    public function headings(): array
    {
        return ['#', 'Code', 'Project Name', 'Fund Source', 'Date Started', 'Date Ended', 'Beneficiaries', 'Status', 'Description'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E40AF']]]];
    }

    public function title(): string { return 'Projects'; }
}

