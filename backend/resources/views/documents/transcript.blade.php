@extends('documents.layout')

@section('title', 'Academic Transcript')

@section('content')
    <h1>ACADEMIC TRANSCRIPT</h1>
    <div class="subtitle">Approved course grades as of {{ $issuedOn }}</div>

    @include('documents._student')

    @foreach ($semesters as $semester)
        <div class="section-title">{{ $semester['name'] }}</div>
        <table class="grid">
            <thead>
                <tr><th style="width: 16%">Code</th><th>Course</th><th class="num" style="width: 10%">Credits</th><th class="num" style="width: 10%">Total</th><th style="width: 9%">Grade</th><th class="num" style="width: 10%">Points</th></tr>
            </thead>
            <tbody>
                @foreach ($semester['grades'] as $grade)
                    <tr>
                        <td>{{ $grade['course']['code'] }}</td>
                        <td>{{ $grade['course']['name'] }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($grade['credits'], 2), '0'), '.') }}</td>
                        <td class="num">{{ $grade['total_score'] === null ? '—' : number_format($grade['total_score'], 2) }}</td>
                        <td>{{ $grade['letter_grade'] }}</td>
                        <td class="num">{{ number_format($grade['grade_point'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($semester['gpa'])
            <div class="summary">Semester GPA {{ number_format($semester['gpa']['gpa'], 2) }} · credits attempted {{ $semester['gpa']['attempted_credits'] }} · earned {{ $semester['gpa']['earned_credits'] }}</div>
        @endif
    @endforeach

    @if ($cumulative)
        <div class="section-title">Cumulative</div>
        <table class="facts">
            <tr><td class="label">Cumulative GPA</td><td><strong>{{ number_format($cumulative['gpa'], 2) }}</strong> (latest attempt of each course)</td></tr>
            <tr><td class="label">Credits earned</td><td>{{ $cumulative['earned_credits'] }} of {{ $cumulative['attempted_credits'] }} attempted</td></tr>
        </table>
    @endif
@endsection
