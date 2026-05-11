<?php

namespace App\Exports;

use App\Models\Training;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TrainingsExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(protected Request $request) {}

    public function collection()
    {
        $query = Training::query()->with('project')->withCount('beneficiaries');
        if ($this->request->filled('search')) {
            $q = $this->request->search;
            $query->where(fn($q2) => $q2->where('training_tile', 'like', "%$q%")->orWhere('facilitator', 'like', "%$q%"));
        }
        if ($this->request->filled('project_id')) {
            $query->where('project_id', $this->request->project_id);
        }

        return $query->latest()->get()->map(fn ($t, $i) => [
            $i + 1,
            $t->training_tile,
            $t->training_type,
            $t->facilitator,
            $t->venue,
            $t->date_conducted?->format('Y-m-d'),
            $t->duration_hours,
            $t->project?->project_name,
            $t->beneficiaries_count,
        ]);
    }

    public function headings(): array
    {
        return ['#', 'Title', 'Type', 'Facilitator', 'Venue', 'Date Conducted', 'Hours', 'Project', 'Participants'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E40AF']]]];
    }

    public function title(): string { return 'Trainings'; }
}

