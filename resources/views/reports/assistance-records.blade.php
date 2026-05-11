@extends('reports.layout')
@section('subtitle', 'Assistance Records Report')
@section('body')
<div class="summary">
    <div class="summary-card"><div class="val">{{ $total }}</div><div class="lbl">Total Records</div></div>
    <div class="summary-card"><div class="val">₱ {{ number_format($totalAmount, 2) }}</div><div class="lbl">Total Amount Released</div></div>
    <div class="summary-card"><div class="val">{{ $individualCount }}</div><div class="lbl">Individual</div></div>
    <div class="summary-card"><div class="val">{{ $groupCount }}</div><div class="lbl">Group</div></div>
</div>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Recipient</th>
            <th>Type</th>
            <th>Assistance Type</th>
            <th>Amount</th>
            <th>Date Released</th>
            <th>Project</th>
            <th>Released By</th>
        </tr>
    </thead>
    <tbody>
        @foreach($records as $i => $r)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                @if($r->recipient_type === 'individual' && $r->beneficiary)
                    {{ $r->beneficiary->last_name }}, {{ $r->beneficiary->first_name }}
                @elseif($r->beneficiaryGroup)
                    {{ $r->beneficiaryGroup->group_name }}
                @else —
                @endif
            </td>
            <td><span class="badge badge-blue">{{ ucfirst($r->recipient_type) }}</span></td>
            <td>{{ $r->assistance_type }}</td>
            <td style="text-align:right">{{ $r->amount ? '₱ '.number_format($r->amount, 2) : '—' }}</td>
            <td>{{ $r->date_released ? \Carbon\Carbon::parse($r->date_released)->format('M j, Y') : '—' }}</td>
            <td>{{ $r->project?->project_name ?? '—' }}</td>
            <td>{{ $r->released_by }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

