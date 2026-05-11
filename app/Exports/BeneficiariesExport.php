<?php

namespace App\Exports;

use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BeneficiariesExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(protected Request $request) {}

    public function collection()
    {
        $query = Beneficiary::query();

        if ($this->request->filled('search')) {
            $q = $this->request->search;
            $query->where(fn($q2) => $q2
                ->where('last_name', 'like', "%$q%")
                ->orWhere('first_name', 'like', "%$q%")
                ->orWhere('beneficiary_code', 'like', "%$q%"));
        }
        if ($this->request->filled('is_active')) {
            $query->where('is_active', $this->request->boolean('is_active'));
        }

        return $query->orderBy('last_name')->get()->map(fn ($b, $i) => [
            $i + 1,
            $b->beneficiary_code,
            $b->last_name,
            $b->first_name . ' ' . $b->middle_name,
            $b->sex,
            $b->civil_status,
            $b->date_of_birth?->format('Y-m-d'),
            $b->address,
            $b->barangay,
            $b->municipality,
            $b->province,
            $b->contact_number,
            $b->beneficiary_type,
            $b->is_active ? 'Active' : 'Inactive',
        ]);
    }

    public function headings(): array
    {
        return ['#', 'Code', 'Last Name', 'First Name', 'Sex', 'Civil Status', 'Date of Birth',
            'Address', 'Barangay', 'Municipality', 'Province', 'Contact', 'Type', 'Status'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E40AF']], 'font' => ['color' => ['argb' => 'FFFFFFFF'], 'bold' => true]]];
    }

    public function title(): string { return 'Beneficiaries'; }
}

