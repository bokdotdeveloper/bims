<?php

namespace App\Exports;

use App\Models\AssistanceRecord;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssistanceRecordsExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(protected Request $request) {}

    public function collection()
    {
        $query = AssistanceRecord::query()->with(['beneficiary', 'beneficiaryGroup', 'project']);
        if ($this->request->filled('search')) {
            $q = $this->request->search;
            $query->where(fn($q2) => $q2
                ->whereHas('beneficiary', fn($q3) => $q3->where('last_name', 'like', "%$q%")->orWhere('first_name', 'like', "%$q%"))
                ->orWhereHas('beneficiaryGroup', fn($q3) => $q3->where('group_name', 'like', "%$q%")));
        }
        if ($this->request->filled('project_id'))   $query->where('project_id', $this->request->project_id);
        if ($this->request->filled('recipient_type')) $query->where('recipient_type', $this->request->recipient_type);

        return $query->latest()->get()->map(fn ($r, $i) => [
            $i + 1,
            ucfirst($r->recipient_type),
            $r->recipient_type === 'individual' && $r->beneficiary
                ? $r->beneficiary->last_name . ', ' . $r->beneficiary->first_name
                : ($r->beneficiaryGroup?->group_name ?? '—'),
            $r->assistance_type,
            $r->amount,
            $r->date_released?->format('Y-m-d'),
            $r->project?->project_name,
            $r->released_by,
            $r->remarks,
        ]);
    }

    public function headings(): array
    {
        return ['#', 'Recipient Type', 'Recipient', 'Assistance Type', 'Amount', 'Date Released', 'Project', 'Released By', 'Remarks'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E40AF']]]];
    }

    public function title(): string { return 'Assistance Records'; }
}

