<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Analytics Report — {{ $semester->academicYear?->code }} {{ $semester->name }}</title>
<style>
    /* dompdf supports CSS 2.1 only; brand colours from docs/branding (navy #0F172A, primary #2563EB, muted #64748B). */
    @page { margin: 24mm 16mm 22mm 16mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1E293B; line-height: 1.4; }
    header { position: fixed; top: -18mm; left: 0; right: 0; border-bottom: 2px solid #2563EB; padding-bottom: 6px; }
    header .name { font-size: 13pt; font-weight: bold; color: #0F172A; }
    header .meta { font-size: 8pt; color: #64748B; }
    footer { position: fixed; bottom: -16mm; left: 0; right: 0; border-top: 1px solid #CBD5E1; padding-top: 5px; font-size: 8pt; color: #64748B; }
    footer .page-number:before { content: counter(page); }

    .doc-title { font-size: 15pt; font-weight: bold; color: #0F172A; letter-spacing: 0.5px; margin: 4px 0 2px 0; }
    .doc-subtitle { font-size: 9pt; color: #64748B; margin-bottom: 14px; }

    .section-title { font-size: 10.5pt; font-weight: bold; color: #0F172A; margin: 14px 0 6px 0; border-bottom: 1px solid #E2E8F0; padding-bottom: 3px; }

    .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .kpi-table td { width: 25%; padding: 6px 8px; vertical-align: top; background: #F8FAFC; border: 1px solid #E2E8F0; }
    .kpi-lbl { font-size: 7.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; color: #64748B; margin-bottom: 2px; }
    .kpi-val { font-size: 13pt; font-weight: bold; color: #0F172A; }
    .kpi-sub { font-size: 7.5pt; color: #64748B; margin-top: 1px; }

    .grid { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 12px; }
    .grid th { background: #F1F5F9; color: #0F172A; text-align: left; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3px; padding: 5px 6px; border-bottom: 1.5px solid #CBD5E1; }
    .grid td { padding: 4px 6px; border-bottom: 1px solid #E2E8F0; font-size: 8.5pt; vertical-align: middle; }
    .grid .num { text-align: right; }
    .grid .center { text-align: center; }

    .two-col { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .two-col td { vertical-align: top; border: none; padding: 0; }
</style>
</head>
<body>
<header>
    <div class="name">{{ $university?->name ?? 'EduCore University' }}</div>
    <div class="meta">
        {{ collect([$university?->address, $university?->phone, $university?->email, $university?->website])->filter()->join(' · ') }}
    </div>
</header>

<footer>
    <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
        <tr>
            <td style="text-align: left; border: none; padding: 0;">
                Executive Analytics & Institutional Report · Generated on {{ $generatedAt }} by EduCore
            </td>
            <td style="text-align: right; border: none; padding: 0;">
                Page <span class="page-number"></span>
            </td>
        </tr>
    </table>
</footer>

<main>
    <div class="doc-title">INSTITUTIONAL ANALYTICS REPORT</div>
    <div class="doc-subtitle">
        Reporting Period: <strong>{{ $semester->academicYear?->code }} — {{ $semester->name }}</strong> (Status: {{ ucfirst($semester->status->value) }})
    </div>

    <!-- KPI Metrics Grid -->
    <table class="kpi-table">
        <tr>
            <td>
                <div class="kpi-lbl">Students Enrolled</div>
                <div class="kpi-val">{{ number_format($overview['students_enrolled']) }}</div>
                <div class="kpi-sub">{{ $overview['enrollments'] }} total enrollments</div>
            </td>
            <td>
                <div class="kpi-lbl">Active Students</div>
                <div class="kpi-val">{{ number_format($overview['students_active']) }}</div>
                <div class="kpi-sub">Across all programs</div>
            </td>
            <td>
                <div class="kpi-lbl">Running Sections</div>
                <div class="kpi-val">{{ number_format($overview['sections']) }}</div>
                <div class="kpi-sub">Active course sections</div>
            </td>
            <td>
                <div class="kpi-lbl">Active Lecturers</div>
                <div class="kpi-val">{{ number_format($overview['lecturers_active']) }}</div>
                <div class="kpi-sub">Assigned teaching staff</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="kpi-lbl">Attendance Rate</div>
                <div class="kpi-val" style="color: #2563EB;">{{ $overview['attendance_rate'] !== null ? $overview['attendance_rate'].'%' : '—' }}</div>
                <div class="kpi-sub">Present + late sessions</div>
            </td>
            <td>
                <div class="kpi-lbl">Approved Grades</div>
                <div class="kpi-val">{{ number_format($overview['grades_approved']) }}</div>
                <div class="kpi-sub">Approved or finalized</div>
            </td>
            <td>
                <div class="kpi-lbl">Pass Rate</div>
                <div class="kpi-val" style="color: #166534;">{{ $overview['pass_rate'] !== null ? $overview['pass_rate'].'%' : '—' }}</div>
                <div class="kpi-sub">Grade point > 0.00</div>
            </td>
            <td>
                <div class="kpi-lbl">Average GPA</div>
                <div class="kpi-val">{{ $overview['average_gpa'] !== null ? number_format($overview['average_gpa'], 2) : '—' }}</div>
                <div class="kpi-sub">Semester grade point avg</div>
            </td>
        </tr>
    </table>

    <!-- Enrollment By Program -->
    <div class="section-title">1. Enrollment Distribution by Academic Program</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 15%;">Program Code</th>
                <th style="width: 45%;">Program Name</th>
                <th style="width: 20%;" class="num">Students Enrolled</th>
                <th style="width: 20%;" class="num">Course Enrollments</th>
            </tr>
        </thead>
        <tbody>
            @forelse($enrollment['by_program'] as $p)
            <tr>
                <td><strong>{{ $p['code'] }}</strong></td>
                <td>{{ $p['name'] }}</td>
                <td class="num">{{ number_format($p['students']) }}</td>
                <td class="num font-semibold">{{ number_format($p['enrollments']) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="center" style="color: #64748B;">No program enrollments recorded for this semester.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Grades & GPA Distribution (2 columns) -->
    <table class="two-col">
        <tr>
            <td style="width: 48%; padding-right: 12px;">
                <div class="section-title">2. Grade Distribution</div>
                <table class="grid">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Grade</th>
                            <th style="width: 35%;" class="num">Students</th>
                            <th style="width: 30%;" class="center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($academic['grade_distribution'] as $g)
                        <tr>
                            <td><strong>{{ $g['grade'] }}</strong></td>
                            <td class="num">{{ number_format($g['total']) }}</td>
                            <td class="center" style="{{ $g['is_pass'] ? 'color: #166534;' : 'color: #991B1B;' }}">
                                {{ $g['is_pass'] ? 'Pass' : 'Fail' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="center" style="color: #64748B;">No approved grades.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td style="width: 48%; padding-left: 12px;">
                <div class="section-title">3. Semester GPA Distribution</div>
                <table class="grid">
                    <thead>
                        <tr>
                            <th style="width: 50%;">GPA Band</th>
                            <th style="width: 50%;" class="num">Student Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($academic['gpa_distribution'] as $gpa)
                        <tr>
                            <td><strong>{{ $gpa['band'] }}</strong></td>
                            <td class="num">{{ number_format($gpa['total']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="center" style="color: #64748B;">No GPA records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Course Performance -->
    @if(!empty($academic['courses']))
    <div class="section-title">4. Course Academic Performance Summary</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 15%;">Course Code</th>
                <th style="width: 37%;">Course Name</th>
                <th style="width: 12%;" class="num">Graded</th>
                <th style="width: 12%;" class="num">Pass Rate</th>
                <th style="width: 12%;" class="num">Avg Score</th>
                <th style="width: 12%;" class="num">Avg Points</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($academic['courses'], 0, 15) as $c)
            <tr>
                <td><strong>{{ $c['code'] }}</strong></td>
                <td>{{ $c['name'] }}</td>
                <td class="num">{{ number_format($c['graded']) }}</td>
                <td class="num" style="{{ $c['pass_rate'] !== null && $c['pass_rate'] < 75 ? 'color: #991B1B; font-weight: bold;' : '' }}">
                    {{ $c['pass_rate'] !== null ? $c['pass_rate'].'%' : '—' }}
                </td>
                <td class="num">{{ $c['average_total'] ?? '—' }}</td>
                <td class="num">{{ $c['average_point'] !== null ? number_format($c['average_point'], 2) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Finance & Administrative Workload -->
    <div class="section-title">5. Institutional Finance & Current Workload</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 12%;">Currency</th>
                <th style="width: 18%;" class="num">Total Invoiced</th>
                <th style="width: 18%;" class="num">Collected</th>
                <th style="width: 18%;" class="num">Outstanding</th>
                <th style="width: 18%;" class="num">Overdue</th>
                <th style="width: 16%;" class="num">Collection Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($administrative['finance'] as $f)
            <tr>
                <td><strong>{{ $f['currency'] }}</strong></td>
                <td class="num">{{ number_format((float) $f['invoiced'], 2) }}</td>
                <td class="num" style="color: #166534;">{{ number_format((float) $f['collected'], 2) }}</td>
                <td class="num" style="color: #991B1B;">{{ number_format((float) $f['outstanding'], 2) }}</td>
                <td class="num" style="color: #991B1B;">{{ number_format((float) $f['overdue'], 2) }} ({{ $f['overdue_count'] }})</td>
                <td class="num font-semibold">{{ $f['collection_rate'] !== null ? $f['collection_rate'].'%' : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="center" style="color: #64748B;">No financial records.</td></tr>
            @endforelse
        </tbody>
    </table>
</main>
</body>
</html>
