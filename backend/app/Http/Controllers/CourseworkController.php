<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AssignmentController as ApiAssignmentController;
use App\Http\Requests\AssignmentRequest;
use App\Http\Requests\GradeSubmissionRequest;
use App\Http\Requests\SubmitAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\CourseMaterialResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\Section;
use App\Services\AssignmentService;
use App\Services\CourseMaterialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Coursework screens (module 9.12): one page per section for its lecturers /
 * staff (create, publish, review and grade) and for enrolled students (submit),
 * plus a student overview across their sections.
 */
class CourseworkController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly ApiAssignmentController $api,
        private readonly CourseMaterialService $materials,
    ) {}

    public function section(Request $request, Section $section): Response
    {
        $this->authorize('viewSection', [Assignment::class, $section]);

        $section->load('offering.course:id,code,name', 'offering.semester.academicYear:id,code');
        $canManage = $request->user()->can('create', [Assignment::class, $section]);
        $canReview = $request->user()->can('viewSubmissions', new Assignment(['section_id' => $section->id]));
        $list = $this->api->listFor($request, $section);

        return Inertia::render('Coursework/Section', [
            'section' => [
                'id' => $section->id,
                'code' => $section->code,
                'course' => ['code' => $section->offering->course->code, 'name' => $section->offering->course->name],
                'semester' => trim(($section->offering->semester->academicYear?->code ?? '').' '.$section->offering->semester->name),
            ],
            'assignments' => AssignmentResource::collection($list)->resolve(),
            // Reviewers get every submission, grouped by assignment.
            'submissions' => $canReview
                ? AssignmentSubmission::query()->whereIn('assignment_id', $list->modelKeys())->with(['enrollment.student', 'file'])->get()
                    ->groupBy('assignment_id')->map(fn ($rows) => SubmissionResource::collection($rows)->resolve())
                : (object) [],
            'types' => Assignment::TYPES,
            'canManage' => $canManage,
            'canReview' => $canReview,
            'acceptedTypes' => config('academics.submission_mimes'),
            'maxKb' => config('academics.submission_max_kb'),
            // Course materials (report 44): the same readers as the coursework.
            'materials' => CourseMaterialResource::collection($this->materials->listFor($section))->resolve(),
            'canShare' => $request->user()->can('create', [CourseMaterial::class, $section]),
            'materialTypes' => config('academics.material_mimes'),
            'materialMaxKb' => config('academics.material_max_kb'),
        ]);
    }

    public function store(AssignmentRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('create', [Assignment::class, $section]);

        $this->assignments->create($section, $request->validated());

        return back()->with('success', 'Assignment created as a draft. Publish it when ready.');
    }

    public function update(AssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $this->assignments->update($assignment, $request->validated());

        return back()->with('success', 'Assignment updated.');
    }

    public function publish(Request $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $published = $request->boolean('published', true);
        $this->assignments->publish($assignment, $published);

        return back()->with('success', $published ? 'Assignment published to students.' : 'Assignment unpublished.');
    }

    public function destroy(Assignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        $this->assignments->delete($assignment);

        return back()->with('success', 'Assignment deleted.');
    }

    public function submit(SubmitAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        $this->authorize('submit', $assignment);

        $submission = $this->assignments->submit($assignment, $request->user()->student, $request->file('file'), $request->user());

        return back()->with('success', $submission->status === 'late' ? 'Submitted — marked late (after the due date).' : 'Submitted.');
    }

    public function grade(GradeSubmissionRequest $request, AssignmentSubmission $submission): RedirectResponse
    {
        $this->authorize('grade', $submission->assignment);

        $data = $request->validated();
        $this->assignments->grade($submission, (float) $data['score'], $data['feedback'] ?? null, $request->user());

        return back()->with('success', 'Submission graded.');
    }

    public function download(AssignmentSubmission $submission): StreamedResponse
    {
        $this->authorize('download', $submission);

        return $this->assignments->download($submission);
    }

    /** Student: published assignments across their current sections. */
    public function mine(Request $request): Response
    {
        $student = $request->user()->student;
        abort_if($student === null, 403, 'No student profile is linked to this account.');

        $enrollments = Enrollment::query()
            ->where('student_id', $student->getKey())
            ->whereIn('status', [Enrollment::STATUS_PENDING, Enrollment::STATUS_CONFIRMED])
            ->with('section.offering.course:id,code,name')
            ->get();

        $assignments = Assignment::query()
            ->whereIn('section_id', $enrollments->pluck('section_id'))
            ->where('is_published', true)
            ->with(['submissions' => fn ($q) => $q->whereIn('enrollment_id', $enrollments->modelKeys())->with('file')])
            ->orderBy('due_at')
            ->get()
            ->map(function (Assignment $assignment) use ($enrollments) {
                $section = $enrollments->firstWhere('section_id', $assignment->section_id)->section;
                $mine = $assignment->submissions->first();

                return [
                    ...(new AssignmentResource($assignment))->resolve(),
                    'course' => ['code' => $section->offering->course->code, 'name' => $section->offering->course->name],
                    'section_code' => $section->code,
                    'my_submission' => $mine ? (new SubmissionResource($mine))->resolve() : null,
                ];
            });

        return Inertia::render('Coursework/Mine', [
            'assignments' => $assignments,
            'acceptedTypes' => config('academics.submission_mimes'),
            'maxKb' => config('academics.submission_max_kb'),
        ]);
    }
}
