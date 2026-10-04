<table class="facts">
    <tr><td class="label">Student</td><td>{{ $student->fullName() }}</td></tr>
    <tr><td class="label">Student ID</td><td>{{ $student->student_number }}</td></tr>
    @if ($program)
        <tr><td class="label">Program</td><td>{{ $program->name }} ({{ $program->code }})</td></tr>
        @if ($program->department)
            <tr><td class="label">Department</td><td>{{ $program->department->name }}</td></tr>
        @endif
    @endif
    <tr><td class="label">Status</td><td>{{ ucfirst($student->status) }}</td></tr>
</table>
