@extends('reports.layout')
@section('subtitle', 'Projects Report')
@section('body')
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Code</th>
            <th>Project Name</th>
            <th>Fund Source</th>
            <th>Date Started</th>
            <th>Date Ended</th>
            <th>Beneficiaries</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($projects as $i => $p)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $p->project_code }}</td>
            <td>{{ $p->project_name }}</td>
            <td>{{ $p->fund_source }}</td>
            <td>{{ $p->date_started ? \Carbon\Carbon::parse($p->date_started)->format('M j, Y') : '—' }}</td>
            <td>{{ $p->date_ended   ? \Carbon\Carbon::parse($p->date_ended)->format('M j, Y')   : '—' }}</td>
            <td style="text-align:center">{{ $p->beneficiaries_count }}</td>
            <td><span class="badge {{ $p->is_active ? 'badge-green' : 'badge-red' }}">{{ $p->is_active ? 'Active' : 'Inactive' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

