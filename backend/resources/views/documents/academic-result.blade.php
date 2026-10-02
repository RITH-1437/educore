@extends('documents.layout')

@section('title', 'Academic Result')

@section('content')
    <h1>ACADEMIC RESULT</h1>
    <div class="subtitle">{{ $semesterName }}</div>

    @include('documents._student')

    <table class="grid" style="margin-top: 16px">
        <thead>
            <tr><th style="width: 16%">Code</th><th>Course</th><th class="num" style="width: 10%">Credits</th><th class="num" style="width: 10%">Total</th><th style="width: 9%">Grade</th><th class="num" style="width: 10%">Points</th></tr>
        </thead>
        <tbody>
            @foreach ($grades as $grade)
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

    @if ($gpa)
        <div class="summary">Semester GPA <strong>{{ number_format($gpa['gpa'], 2) }}</strong> · credits attempted {{ $gpa['attempted_credits'] }} · earned {{ $gpa['earned_credits'] }}</div>
    @endif
@endsection
