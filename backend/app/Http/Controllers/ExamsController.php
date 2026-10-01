<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ExamController as ApiExamController;
use App\Http\Requests\ExamRequest;
use App\Http\Requests\RecordExamResultsRequest;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\Section;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Examination screens (module 9.13): one page per section for its lecturers /
 * staff (plan exams, enter and release results) and enrolled students (schedule
 * and released results), plus a student overview across their sections.
 */
class ExamsController extends Controller
{
    public function __construct(
        private readonly ExamService $exams,
        private readonly ApiExamController $api,
    ) {}

    public function section(Request $request, Section $section): Response
    {
        $this->authorize('viewSection', [Exam::class, $section]);

        $section->load('offering.course:id,code,name', 'offering.semester.academicYear:id,code');
        $canManage = $request->user()->can('create', [Exam::class, $section]);
        $canReview = $request->user()->can('viewResults', new Exam(['section_id' => $section->id]));
        $list = $this->api->listFor($request, $section);

        // Reviewers open one exam's results grid at a time (`?exam=`).
        $selected = $canReview ? ($list->firstWhere('id', (int) $request->query('exam')) ?? $list->first()) : null;
        $student = $request->user()->student;
        $mine = $student && ! $canReview ? $this->exams->forStudent($student)->where('section_id', $section->id)->keyBy('id') : collect();

        return Inertia::render('Exams/Section', [
            'section' => [
                'id' => $section->id,
                'code' => $section->code,
                'course' => ['code' => $section->offering->course->code, 'name' => $section->offering->course->name],
                'semester' => trim(($section->offering->semester->academicYear?->code ?? '').' '.$section->offering->semester->name),
                'locked' => $section->offering->semester->status->value === 'completed',
            ],
            'exams' => $list->map(fn (Exam $exam) => [
                ...(new ExamResource($exam))->resolve($request),
                'my_result' => $mine->get($exam->id)['my_result'] ?? null,
            ])->values(),
            'selectedId' => $selected?->id,
            'roster' => $selected ? $this->exams->roster($selected) : [],
            'totalWeight' => round((float) $list->sum('weight'), 2),
            'types' => Exam::TYPES,
            'canManage' => $canManage,
            'canReview' => $canReview,
            'today' => today()->toDateString(),
        ]);
    }

    public function store(ExamRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('create', [Exam::class, $section]);

        $exam = $this->exams->create($section, $request->validated());

        return redirect()->route('exams.section', ['section' => $section->id, 'exam' => $exam->id])->with('success', 'Exam created. Results stay hidden from students until you release them.');
    }

    public function update(ExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $this->exams->update($exam, $request->validated());

        return back()->with('success', 'Exam updated.');
    }

    public function publish(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $published = $request->boolean('published', true);
        $this->exams->publish($exam, $published);

        return back()->with('success', $published ? 'Results released to students.' : 'Results hidden from students.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorize('delete', $exam);

        $section = $exam->section_id;
        $this->exams->delete($exam);

        return redirect()->route('exams.section', ['section' => $section])->with('success', 'Exam deleted.');
    }

    public function record(RecordExamResultsRequest $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $saved = $this->exams->record($exam, $request->validated('results'), $request->user());

        return back()->with('success', "Saved {$saved} result".($saved === 1 ? '' : 's').'.');
    }

    /** Student: exam schedule and released results across their sections. */
    public function mine(Request $request): Response
    {
        $student = $request->user()->student;
        abort_if($student === null, 403, 'No student profile is linked to this account.');

        return Inertia::render('Exams/Mine', [
            'exams' => $this->exams->forStudent($student)->values(),
            'today' => today()->toDateString(),
        ]);
    }
}
