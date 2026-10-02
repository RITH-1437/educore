@extends('documents.layout')

@section('title', 'Student Certificate')

@section('content')
    <h1>STUDENT CERTIFICATE</h1>
    <div class="subtitle">
        @if ($student->status === 'graduated')
            This is to certify that the person below has graduated from {{ $university?->name ?? 'this university' }}.
        @else
            This is to certify that the person below is a registered student of {{ $university?->name ?? 'this university' }}.
        @endif
    </div>

    @include('documents._student')

    <table class="facts" style="margin-top: 6px">
        @if ($student->date_of_birth)
            <tr><td class="label">Date of birth</td><td>{{ $student->date_of_birth->toDateString() }}</td></tr>
        @endif
        @if ($student->enrollment_date)
            <tr><td class="label">Admitted on</td><td>{{ $student->enrollment_date->toDateString() }}</td></tr>
        @endif
        @if ($programEnrolment?->started_on)
            <tr><td class="label">Program started</td><td>{{ $programEnrolment->started_on->toDateString() }}</td></tr>
        @endif
        @if ($student->status === 'graduated' && $programEnrolment?->ended_on)
            <tr><td class="label">Program completed</td><td>{{ $programEnrolment->ended_on->toDateString() }}</td></tr>
        @endif
    </table>

    <p style="margin-top: 18px">Issued on request of the student for official purposes.</p>
@endsection
