@extends('documents.layout')

@section('title', 'Internship Letter')

@section('content')
    <h1>INTERNSHIP LETTER</h1>
    <div class="subtitle">To {{ $internship->company->name }}@if ($internship->supervisor_name), attention {{ $internship->supervisor_name }}@endif</div>

    <p>
        {{ $university?->name ?? 'This university' }} confirms that the student below
        @switch($internship->status)
            @case('completed')
                has completed an internship
                @break
            @case('in_progress')
                is currently undertaking an internship
                @break
            @default
                has been approved to undertake an internship
        @endswitch
        at {{ $internship->company->name }} as part of their studies.
    </p>

    @include('documents._student')

    <div class="section-title">Placement</div>
    <table class="facts">
        <tr><td class="label">Host company</td><td>{{ $internship->company->name }}@if ($internship->company->industry) ({{ $internship->company->industry }})@endif</td></tr>
        @if ($internship->company->address)
            <tr><td class="label">Address</td><td>{{ $internship->company->address }}</td></tr>
        @endif
        <tr><td class="label">Position</td><td>{{ $internship->position_title }}</td></tr>
        <tr><td class="label">Period</td><td>{{ $internship->start_date?->toDateString() ?? 'to be agreed' }} – {{ $internship->end_date?->toDateString() ?? 'to be agreed' }}</td></tr>
        @if ($internship->supervisor_name)
            <tr><td class="label">Company supervisor</td><td>{{ $internship->supervisor_name }}</td></tr>
        @endif
    </table>

    <p style="margin-top: 18px">We thank the host company for supporting our student's practical training. Please contact the Registrar's Office with any question about this placement.</p>
@endsection
