<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\GradeController as ApiGradeController;
use App\Http\Requests\ComputeGradesRequest;
use App\Http\Requests\GradingConfigRequest;
use App\Http\Requests\GradingScaleRequest;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Section;
use App\Services\GpaService;
use App\Services\GradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Grades & GPA screens (module 9.14): the section grade sheet (lecturers and
 * staff), the approvals queue (staff), the grading scale, and the student's
 * grades and GPA.
 */
class GradesController extends Controller
{
    public function __construct(
        private readonly GradingService $grading,
        private readonly GpaService $gpa,
        private readonly ApiGradeController $api,
    ) {}

    /** Staff: sections with grades, submitted ones (awaiting approval) by default. */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Grade::class);

        $status = $request->query('status') === 'all' ? 'all' : 'submitted';

        $counts = DB::table('grades')
            ->join('enrollments', 'enrollments.id', '=', 'grades.enrollment_id')
            ->whereNull('grades.deleted_at')
            ->whereIn('enrollments.status', GradingService::GRADED_STATUSES)
            ->groupBy('enrollments.section_id')
            ->select('enrollments.section_id')
            ->selectRaw("count(*) filter (where grades.status = 'draft') as draft")
            ->selectRaw("count(*) filter (where grades.status = 'submitted') as submitted")
            ->selectRaw("count(*) filter (where grades.status = 'approved') as approved")
            ->selectRaw("count(*) filter (where grades.status = 'finalized') as finalized")
            ->when($status === 'submitted', fn ($q) => $q->havingRaw("count(*) filter (where grades.status = 'submitted') > 0"));

        $sections = Section::query()
            ->visibleTo($request->user())
            ->joinSub($counts, 'counts', 'counts.section_id', '=', 'sections.id')
            ->with('offering.course:id,code,name', 'offering.semester.academicYear:id,code')
            ->orderByDesc('counts.submitted')
            ->orderBy('sections.id')
            ->select('sections.*', 'counts.draft', 'counts.submitted', 'counts.approved', 'counts.finalized')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Section $section) => [
                'id' => $section->id,
                'code' => $section->code,
                'course' => ['code' => $section->offering->course->code, 'name' => $section->offering->course->name],
                'semester' => trim(($section->offering->semester->academicYear?->code ?? '').' '.$section->offering->semester->name),
                'counts' => ['draft' => (int) $section->draft, 'submitted' => (int) $section->submitted, 'approved' => (int) $section->approved, 'finalized' => (int) $section->finalized],
            ]);

        return Inertia::render('Grades/Index', [
            'sections' => $sections,
            'status' => $status,
            'canApprove' => $request->user()->can('approve', Grade::class),
        ]);
    }

    public function section(Request $request, Section $section): Response
    {
        $this->authorize('viewSection', [Grade::class, $section]);

        $section->load('offering.course:id,code,name,credits', 'offering.semester.academicYear:id,code');

        return Inertia::render('Grades/Section', [
            'section' => [
                'id' => $section->id,
                'code' => $section->code,
                'course' => ['id' => $section->offering->course->id, 'code' => $section->offering->course->code, 'name' => $section->offering->course->name, 'credits' => (float) $section->offering->course->credits],
                'semester' => trim(($section->offering->semester->academicYear?->code ?? '').' '.$section->offering->semester->name),
            ],
            'sheet' => $this->api->sheetPayload($section),
            'scale' => $this->api->scalePayload()['data'],
            'canGrade' => $request->user()->can('grade', [Grade::class, $section]),
            'canApprove' => $request->user()->can('approve', Grade::class),
            'canReopen' => $request->user()->can('reopen', Grade::class),
        ]);
    }

    public function compute(ComputeGradesRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('grade', [Grade::class, $section]);

        $saved = $this->grading->compute($section, $request->user(), $request->remarksByEnrollment());

        return back()->with('success', "Saved {$saved} draft grade".($saved === 1 ? '' : 's').'.');
    }

    public function submit(Request $request, Section $section): RedirectResponse
    {
        $this->authorize('grade', [Grade::class, $section]);

        $saved = $this->grading->submit($section, $request->user());

        return back()->with('success', "Submitted {$saved} grade".($saved === 1 ? '' : 's').' for approval.');
    }

    public function approve(Section $section): RedirectResponse
    {
        $this->authorize('approve', Grade::class);

        $saved = $this->grading->approve($section);

        return back()->with('success', "Approved {$saved} grade".($saved === 1 ? '' : 's').'. GPAs have been recalculated.');
    }

    public function finalize(Section $section): RedirectResponse
    {
        $this->authorize('approve', Grade::class);

        $saved = $this->grading->finalize($section);

        return back()->with('success', "Finalized {$saved} grade".($saved === 1 ? '' : 's').'. They are now locked.');
    }

    public function reopen(Request $request, Section $section): RedirectResponse
    {
        $this->authorize('reopen', Grade::class);

        $saved = $this->grading->reopen($section, $request->validate(['reason' => ['required', 'string', 'max:500']])['reason']);

        return back()->with('success', "Reopened {$saved} grade".($saved === 1 ? '' : 's').'.');
    }

    public function returnToDraft(Section $section): RedirectResponse
    {
        $this->authorize('approve', Grade::class);

        $saved = $this->grading->returnToDraft($section);

        return back()->with('success', "Returned {$saved} grade".($saved === 1 ? '' : 's').' to draft.');
    }

    public function scale(Request $request): Response
    {
        $this->authorize('viewScale', Grade::class);

        return Inertia::render('Grades/Scale', [
            'scale' => $this->api->scalePayload(),
            'canEdit' => $request->user()->can('configure', Grade::class),
        ]);
    }

    public function updateScale(GradingScaleRequest $request): RedirectResponse
    {
        $this->authorize('configure', Grade::class);

        $this->grading->saveScale($request->validated('bands'));

        return back()->with('success', 'Grading scale saved. Approved grades keep their letters; drafts use the new scale when recomputed.');
    }

    public function updateConfig(GradingConfigRequest $request, Course $course): RedirectResponse
    {
        $this->authorize('configure', Grade::class);

        $this->grading->saveConfig($course, $request->validated());

        return back()->with('success', 'Grading weights saved.');
    }

    /** Student: approved grades and GPA. */
    public function mine(Request $request): Response
    {
        $student = $request->user()->student;
        abort_if($student === null, 403, 'No student profile is linked to this account.');

        return Inertia::render('Grades/Mine', [
            'grades' => $this->grading->forStudent($student->id)->values(),
            'gpa' => $this->gpa->summary($student),
        ]);
    }
}
