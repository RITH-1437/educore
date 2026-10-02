@extends('documents.layout')

@section('title', 'Enrollment Certificate')

@section('content')
    <h1>ENROLLMENT CERTIFICATE</h1>
    <div class="subtitle">This is to certify that the student below is enrolled at {{ $university?->name ?? 'this university' }}.</div>

    @include('documents._student')

    @if ($enrollments->isNotEmpty())
        <div class="section-title">Courses registered{{ $semesterName ? ' — '.$semesterName : '' }}</div>
        <table class="grid">
            <thead>
                <tr><th style="width: 18%">Code</th><th>Course</th><th style="width: 12%">Section</th><th class="num" style="width: 12%">Credits</th></tr>
            </thead>
            <tbody>
                @foreach ($enrollments as $enrollment)
                    <tr>
                        <td>{{ $enrollment->section->offering->course->code }}</td>
                        <td>{{ $enrollment->section->offering->course->name }}</td>
                        <td>{{ $enrollment->section->code }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $enrollment->section->offering->course->credits, 2), '0'), '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="summary">Total credits: {{ rtrim(rtrim(number_format($enrollments->sum(fn ($e) => (float) $e->section->offering->course->credits), 2), '0'), '.') }}</div>
    @else
        <p class="summary">The student currently holds no open course registration.</p>
    @endif

    <p style="margin-top: 18px">Issued on request of the student for official purposes.</p>
@endsection
