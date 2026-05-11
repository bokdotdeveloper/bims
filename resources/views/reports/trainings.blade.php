@extends('reports.layout')
@section('subtitle', 'Trainings Report')
@section('body')
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Title</th>
            <th>Type</th>
            <th>Facilitator</th>
            <th>Venue</th>
            <th>Date Conducted</th>
            <th>Hours</th>
            <th>Project</th>
            <th>Participants</th>
        </tr>
    </thead>
    <tbody>
        @foreach($trainings as $i => $t)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $t->training_tile }}</td>
            <td>{{ $t->training_type }}</td>
            <td>{{ $t->facilitator }}</td>
            <td>{{ $t->venue }}</td>
            <td>{{ $t->date_conducted ? \Carbon\Carbon::parse($t->date_conducted)->format('M j, Y') : '—' }}</td>
            <td style="text-align:center">{{ $t->duration_hours }}</td>
            <td>{{ $t->project?->project_name ?? '—' }}</td>
            <td style="text-align:center">{{ $t->beneficiaries_count }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

