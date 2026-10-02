<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\InternshipController as Api;
use App\Http\Requests\InternshipCompanyRequest;
use App\Http\Requests\InternshipReportRequest;
use App\Http\Requests\InternshipRequest;
use App\Http\Resources\InternshipResource;
use App\Models\Internship;
use App\Models\InternshipCompany;
use App\Models\InternshipEvaluation;
use App\Models\InternshipReport;
use App\Services\InternshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Internship screens (module 9.22): the student's portal, the staff queue,
 * the internship page (review, reports, evaluations) and companies.
 */
class InternshipsController extends Controller
{
    public function __construct(
        private readonly InternshipService $internships,
        private readonly Api $api,
    ) {}

    /** Student portal: current application and history. */
    public function mine(Request $request): Response
    {
        $this->authorize('apply', Internship::class);

        $all = Internship::query()->where('student_id', $request->user()->student->getKey())->with(Api::DETAIL)->latest('id')->get();
        $current = $all->first(fn (Internship $i) => ! in_array($i->status, Internship::FINAL_STATUSES, true));

        return Inertia::render('Internships/Mine', [
            'current' => $current ? (new InternshipResource($current))->resolve() : null,
            'history' => InternshipResource::collection($all->reject(fn ($i) => $i->is($current))->values())->resolve(),
            'companies' => Api::companyList(true),
        ]);
    }

    public function store(InternshipRequest $request): RedirectResponse
    {
        $this->authorize('apply', Internship::class);

        $this->internships->apply($request->user()->student, $request->validated());

        return back()->with('success', 'Draft saved. Submit it when it is ready for review.');
    }

    /** Staff queue. */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Internship::class);

        return Inertia::render('Internships/Index', [
            'internships' => InternshipResource::collection($this->api->listFor($request)),
            'filters' => ['status' => $request->input('filters.status')],
            'statuses' => Internship::STATUSES,
        ]);
    }

    public function show(Request $request, Internship $internship): Response
    {
        $this->authorize('view', $internship);

        return Inertia::render('Internships/Show', [
            'internship' => (new InternshipResource($internship->load(Api::DETAIL)))->resolve(),
            'companies' => Api::companyList(true),
            'canProcess' => $request->user()->can('process', Internship::class),
            'isOwner' => $request->user()->can('act', $internship),
            'ratings' => InternshipEvaluation::RATINGS,
            'reportTypes' => InternshipReport::TYPES,
        ]);
    }

    public function update(InternshipRequest $request, Internship $internship): RedirectResponse
    {
        $manager = $request->user()->can('process', Internship::class);
        abort_unless($manager || $request->user()->can('act', $internship), 403);

        $this->internships->update($internship, $request->validated(), $manager);

        return back()->with('success', 'Internship updated.');
    }

    public function transition(Request $request, Internship $internship, string $action): RedirectResponse
    {
        $moved = $this->api->runTransition($request, $internship, $action);

        return back()->with('success', 'Internship '.str_replace('_', ' ', $moved->status).'.');
    }

    public function report(InternshipReportRequest $request, Internship $internship): RedirectResponse
    {
        $this->authorize('act', $internship);

        $this->internships->addReport($internship, $request->safe()->except('file'), $request->file('file'), $request->user());

        return back()->with('success', 'Report submitted.');
    }

    public function reviewReport(Request $request, InternshipReport $report): RedirectResponse
    {
        $this->authorize('process', Internship::class);

        $this->internships->reviewReport($report, $request->validate(['reviewer_comment' => ['nullable', 'string', 'max:2000']])['reviewer_comment'] ?? null);

        return back()->with('success', 'Report marked reviewed.');
    }

    public function downloadReport(InternshipReport $report): StreamedResponse
    {
        $this->authorize('view', $report->internship);

        return $this->internships->downloadReport($report);
    }

    public function evaluate(Request $request, Internship $internship): RedirectResponse
    {
        $this->authorize('process', Internship::class);

        $this->internships->evaluate($internship, Api::evaluationData($request), $request->user());

        return back()->with('success', 'Evaluation saved.');
    }

    public function companies(Request $request): Response
    {
        $this->authorize('viewAny', Internship::class);

        return Inertia::render('Internships/Companies', [
            'companies' => Api::companyList(false),
            'canManage' => $request->user()->can('process', Internship::class),
        ]);
    }

    public function storeCompany(InternshipCompanyRequest $request): RedirectResponse
    {
        $this->authorize('process', Internship::class);

        $this->internships->saveCompany(null, $request->validated());

        return back()->with('success', 'Company added.');
    }

    public function updateCompany(InternshipCompanyRequest $request, InternshipCompany $company): RedirectResponse
    {
        $this->authorize('process', Internship::class);

        $this->internships->saveCompany($company, $request->validated());

        return back()->with('success', 'Company updated.');
    }
}
