@extends('reports.layout')
@section('subtitle', 'Beneficiaries Report')
@section('body')
<div class="summary">
    <div class="summary-card"><div class="val">{{ $total }}</div><div class="lbl">Total Beneficiaries</div></div>
    <div class="summary-card"><div class="val">{{ $active }}</div><div class="lbl">Active</div></div>
    <div class="summary-card"><div class="val">{{ $inactive }}</div><div class="lbl">Inactive</div></div>
    <div class="summary-card"><div class="val">{{ $male }}</div><div class="lbl">Male</div></div>
    <div class="summary-card"><div class="val">{{ $female }}</div><div class="lbl">Female</div></div>
</div>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Code</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Sex</th>
            <th>Civil Status</th>
            <th>Barangay</th>
            <th>Municipality</th>
            <th>Contact</th>
            <th>Type</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($beneficiaries as $i => $b)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $b->beneficiary_code }}</td>
            <td>{{ $b->last_name }}</td>
            <td>{{ $b->first_name }} {{ $b->middle_name }}</td>
            <td>{{ $b->sex }}</td>
            <td>{{ $b->civil_status }}</td>
            <td>{{ $b->barangay }}</td>
            <td>{{ $b->municipality }}</td>
            <td>{{ $b->contact_number }}</td>
            <td>{{ $b->beneficiary_type }}</td>
            <td><span class="badge {{ $b->is_active ? 'badge-green' : 'badge-red' }}">{{ $b->is_active ? 'Active' : 'Inactive' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

