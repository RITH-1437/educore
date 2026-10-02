<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComputeGradesRequest;
use App\Http\Requests\GradingConfigRequest;
use App\Http\Requests\GradingScaleRequest;
use App\Models\Course;
use App\Models\CourseGradingConfig;
use App\Models\Grade;
use App\Models\GradingScale;
use App\Models\Section;
use App\Models\Student;
use App\Services\GpaService;
use App\Services\GradingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Grades & GPA endpoints (module 9.14).
 */
class GradeController extends Controller
{
    public function __construct(
        private readonly GradingService $grading,
        private readonly GpaService $gpa,
    ) {}

    #[OA\Get(
        path: '/sections/{section}/grades',
        summary: "A section's grade sheet",
        description: 'Per student: component percentages, the live computed total / letter / point, and the stored grade (draft, submitted or approved). Includes the course weights used.',
        operationId: 'getSectionGrades',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Grade sheet.', content: new OA\JsonContent(ref: '#/components/schemas/GradeSheetResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or a lecturer of the section.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function sheet(Section $section): JsonResponse
    {
        $this->authorize('viewSection', [Grade::class, $section]);

        return response()->json($this->sheetPayload($section));
    }

    #[OA\Post(
        path: '/sections/{section}/grades',
        summary: 'Compute draft grades',
        description: 'Saves the computed totals as draft grades for every confirmed / completed enrollment. Submitted and approved grades are left untouched.',
        operationId: 'computeSectionGrades',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(ref: '#/components/schemas/ComputeGradesRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Updated grade sheet with `saved` count.', content: new OA\JsonContent(ref: '#/components/schemas/GradeSheetResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of the section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function compute(ComputeGradesRequest $request, Section $section): JsonResponse
    {
        $this->authorize('grade', [Grade::class, $section]);

        $saved = $this->grading->compute($section, $request->user(), $request->remarksByEnrollment());

        return response()->json(['saved' => $saved, ...$this->sheetPayload($section)]);
    }

    #[OA\Post(
        path: '/sections/{section}/grades/submit',
        summary: 'Submit draft grades for approval',
        description: 'Every graded student needs a draft with a letter grade (422 otherwise); 409 when there is nothing to submit.',
        operationId: 'submitSectionGrades',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Updated grade sheet with `saved` count.', content: new OA\JsonContent(ref: '#/components/schemas/GradeSheetResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a lecturer of the section or a manager.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'No draft grades.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Missing or blank grades.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function submit(Request $request, Section $section): JsonResponse
    {
        $this->authorize('grade', [Grade::class, $section]);

        $saved = $this->grading->submit($section, $request->user());

        return response()->json(['saved' => $saved, ...$this->sheetPayload($section)]);
    }

    #[OA\Post(
        path: '/sections/{section}/grades/approve',
        summary: 'Approve submitted grades',
        description: 'Approved grades count toward GPA and prerequisites; confirmed enrollments become completed and the students\' GPA is recomputed. 409 when nothing is submitted.',
        operationId: 'approveSectionGrades',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Updated grade sheet with `saved` count.', content: new OA\JsonContent(ref: '#/components/schemas/GradeSheetResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'No submitted grades.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function approve(Section $section): JsonResponse
    {
        $this->authorize('approve', Grade::class);

        $saved = $this->grading->approve($section);

        return response()->json(['saved' => $saved, ...$this->sheetPayload($section)]);
    }

    #[OA\Post(
        path: '/sections/{section}/grades/return',
        summary: 'Return grades to draft',
        description: 'Submitted and approved grades go back to draft so the lecturer can recompute; affected GPAs are recomputed. 409 when there is nothing to return.',
        operationId: 'returnSectionGrades',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Updated grade sheet with `saved` count.', content: new OA\JsonContent(ref: '#/components/schemas/GradeSheetResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Nothing to return.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function returnToDraft(Section $section): JsonResponse
    {
        $this->authorize('approve', Grade::class);

        $saved = $this->grading->returnToDraft($section);

        return response()->json(['saved' => $saved, ...$this->sheetPayload($section)]);
    }

    #[OA\Post(
        path: '/sections/{section}/grades/finalize',
        summary: 'Finalize approved grades',
        description: 'Locks approved grades: they can no longer be returned to draft (only reopened by a Super Admin). GPA is unchanged. 409 when nothing is approved.',
        operationId: 'finalizeSectionGrades',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Updated grade sheet with `saved` count.', content: new OA\JsonContent(ref: '#/components/schemas/GradeSheetResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'No approved grades.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function finalize(Section $section): JsonResponse
    {
        $this->authorize('approve', Grade::class);

        $saved = $this->grading->finalize($section);

        return response()->json(['saved' => $saved, ...$this->sheetPayload($section)]);
    }

    #[OA\Post(
        path: '/sections/{section}/grades/reopen',
        summary: 'Reopen finalized grades (Super Admin)',
        description: 'Finalized → approved so grades can be returned and corrected; a reason is required and audited. 409 when nothing is finalized.',
        operationId: 'reopenSectionGrades',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'section', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['reason'], properties: [new OA\Property(property: 'reason', type: 'string', maxLength: 500)])),
        responses: [
            new OA\Response(response: 200, description: 'Updated grade sheet with `saved` count.', content: new OA\JsonContent(ref: '#/components/schemas/GradeSheetResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'No finalized grades.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Reason missing.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function reopen(Request $request, Section $section): JsonResponse
    {
        $this->authorize('reopen', Grade::class);

        $saved = $this->grading->reopen($section, $request->validate(['reason' => ['required', 'string', 'max:500']])['reason']);

        return response()->json(['saved' => $saved, ...$this->sheetPayload($section)]);
    }

    #[OA\Get(
        path: '/students/{student}/grades',
        summary: "A student's approved grades and GPA",
        operationId: 'getStudentGrades',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Approved grades (oldest first) and GPA.', content: new OA\JsonContent(ref: '#/components/schemas/StudentGradesResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or the student themself.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function student(Student $student): JsonResponse
    {
        $this->authorize('viewStudent', [Grade::class, $student]);

        return response()->json(['data' => $this->grading->forStudent($student->id), 'gpa' => $this->gpa->summary($student)]);
    }

    #[OA\Get(
        path: '/students/{student}/gpa',
        summary: "A student's semester and cumulative GPA",
        description: 'Credit-weighted over approved grades; cumulative counts the latest attempt of each course.',
        operationId: 'getStudentGpa',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'student', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'GPA summary.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GpaSummary')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff or the student themself.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function gpa(Student $student): JsonResponse
    {
        $this->authorize('viewStudent', [Grade::class, $student]);

        return response()->json(['data' => $this->gpa->summary($student)]);
    }

    #[OA\Get(
        path: '/grading-scale',
        summary: 'The active grading scale',
        description: 'Bands from the highest grade down. Readable by every signed-in role.',
        operationId: 'getGradingScale',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Scale.', content: new OA\JsonContent(ref: '#/components/schemas/GradingScaleResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function scale(): JsonResponse
    {
        $this->authorize('viewScale', Grade::class);

        return response()->json($this->scalePayload());
    }

    #[OA\Put(
        path: '/grading-scale',
        summary: 'Replace the bands of the active grading scale',
        description: 'Each band gives its minimum percentage; the maximum is derived from the next band, the top band ends at 100. The lowest band must start at 0 and grade points may not decrease as the percentage rises. Approved grades keep their stored letters; drafts pick up the new scale on the next compute.',
        operationId: 'updateGradingScale',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GradingScaleRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Saved scale.', content: new OA\JsonContent(ref: '#/components/schemas/GradingScaleResponse')),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function updateScale(GradingScaleRequest $request): JsonResponse
    {
        $this->authorize('configure', Grade::class);

        $this->grading->saveScale($request->validated('bands'));

        return response()->json($this->scalePayload());
    }

    #[OA\Get(
        path: '/courses/{course}/grading-config',
        summary: "A course's grading weights",
        description: '`is_default` is true when the course has no saved weights and the defaults (10/25/20/40/5) apply.',
        operationId: 'getCourseGradingConfig',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'course', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        responses: [
            new OA\Response(response: 200, description: 'Weights.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GradingConfig')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not staff.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function config(Course $course): JsonResponse
    {
        $this->authorize('viewConfig', [Grade::class, $course]);

        return response()->json(['data' => self::configPayload($this->grading->configFor($course))]);
    }

    #[OA\Put(
        path: '/courses/{course}/grading-config',
        summary: "Save a course's grading weights",
        description: 'Each weight 0–100; together exactly 100 (422 otherwise).',
        operationId: 'updateCourseGradingConfig',
        tags: ['Grades'],
        security: [['sanctum' => []]],
        parameters: [new OA\PathParameter(name: 'course', required: true, schema: new OA\Schema(type: 'integer', format: 'int64'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GradingConfigRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Saved weights.', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/GradingConfig')])),
            new OA\Response(response: 401, description: 'Unauthenticated.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Not a Super Admin or University Admin.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ]
    )]
    public function updateConfig(GradingConfigRequest $request, Course $course): JsonResponse
    {
        $this->authorize('configure', Grade::class);

        return response()->json(['data' => self::configPayload($this->grading->saveConfig($course, $request->validated()))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function sheetPayload(Section $section): array
    {
        $sheet = $this->grading->sheet($section);

        return [
            'weights' => $sheet['weights'],
            'components' => $sheet['components'],
            'counts' => $this->grading->statusCounts($section),
            'data' => $sheet['rows'],
        ];
    }

    /**
     * @return array{name: string, data: list<array<string, mixed>>}
     */
    public function scalePayload(): array
    {
        return [
            'name' => $this->grading->activeScaleName(),
            'data' => $this->grading->activeScale()->map(fn (GradingScale $band) => [
                'grade' => $band->grade,
                'min_percentage' => (float) $band->min_percentage,
                'max_percentage' => (float) $band->max_percentage,
                'grade_point' => (float) $band->grade_point,
                'is_pass' => $band->is_pass,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function configPayload(CourseGradingConfig $config): array
    {
        return [
            'course_id' => $config->course_id,
            'attendance_weight' => (float) $config->attendance_weight,
            'assignment_weight' => (float) $config->assignment_weight,
            'midterm_weight' => (float) $config->midterm_weight,
            'final_weight' => (float) $config->final_weight,
            'practical_weight' => (float) $config->practical_weight,
            'is_default' => ! $config->exists,
        ];
    }
}
