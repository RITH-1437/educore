<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ScheduleEntryRequest;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Services\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "My timetable" for students and lecturers, and the schedule actions used by
 * the offering manage page (module 9.10).
 */
class TimetableController extends Controller
{
    public function __construct(
        private readonly TimetableService $timetable,
    ) {}

    public function mine(Request $request): Response
    {
        $user = $request->user();

        $entries = match (true) {
            $user->isRole(Role::Student->value) && $user->student !== null => $this->timetable->forStudent($user->student),
            $user->isRole(Role::Lecturer->value) && $user->lecturer !== null => $this->timetable->forLecturer($user->lecturer),
            default => abort(403, 'Only students and lecturers with a profile have a personal timetable.'),
        };

        return Inertia::render('Timetable/Index', [
            'entries' => $entries,
            'days' => ScheduleEntry::DAYS,
            'owner' => $user->isRole(Role::Student->value) ? 'student' : 'lecturer',
        ]);
    }

    public function store(ScheduleEntryRequest $request, Section $section): RedirectResponse
    {
        $this->authorize('update', $section->offering);

        $this->timetable->addEntry($section, $request->validated());

        return back()->with('success', 'Class time added.');
    }

    public function destroy(ScheduleEntry $entry): RedirectResponse
    {
        $this->authorize('update', $entry->section->offering);

        $this->timetable->removeEntry($entry);

        return back()->with('success', 'Class time removed.');
    }
}
