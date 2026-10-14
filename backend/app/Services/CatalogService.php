<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\ScheduleEntry;
use App\Models\Section;
use App\Models\Semester;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The public course catalog (report 53), modelled on a Moodle front page:
 * departments as categories, course search, and the courses offered this
 * semester with who teaches them.
 *
 * What is public — and nothing more:
 * - active courses of active departments (no drafts, no archived courses);
 * - for "this semester" (the latest open semester, else the latest one —
 *   `AnalyticsService::defaultSemester`), the published / open / closed
 *   offerings and their open / active / closed sections: section code, weekly
 *   times and room, and lecturers' title and name when
 *   `academics.catalog.show_lecturers` is on;
 * - no enrollment numbers, seats, students, grades or contact details.
 */
class CatalogService
{
    public const PER_PAGE = 12;

    public const PUBLIC_OFFERINGS = ['published', 'open', 'closed'];

    public const PUBLIC_SECTIONS = ['open', 'active', 'closed'];

    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    /** "This semester" for the catalog. */
    public function semester(): ?Semester
    {
        return $this->analytics->defaultSemester()?->loadMissing('academicYear:id,code');
    }

    /**
     * Active departments with at least one public course, and how many.
     *
     * @return Collection<int, Department>
     */
    public function departments(): Collection
    {
        return Department::query()->active()
            ->withCount(['courses' => fn (Builder $courses) => $courses->where('status', Course::STATUS_ACTIVE)])
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'description'])
            ->filter(fn (Department $department) => $department->courses_count > 0)
            ->values();
    }

    /**
     * Public courses, A–Z by code. Filters: `search` (code, name, description),
     * `department_id`, `offered` (only courses taught this semester).
     *
     * @param  array{search?: ?string, department_id?: ?int, offered?: ?bool}  $filters
     * @return LengthAwarePaginator<int, Course>
     */
    public function courses(array $filters): LengthAwarePaginator
    {
        $semester = $this->semester();
        $search = trim((string) ($filters['search'] ?? ''));

        return $this->publicCourses()
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('code', 'ilike', "%{$search}%")
                ->orWhere('name', 'ilike', "%{$search}%")
                ->orWhere('description', 'ilike', "%{$search}%")))
            ->when($filters['department_id'] ?? null, fn (Builder $q, $id) => $q->where('department_id', $id))
            ->when(! empty($filters['offered']), fn (Builder $q) => $semester
                ? $q->whereHas('offerings', fn (Builder $o) => $this->publicOfferings($o, $semester))
                : $q->whereRaw('false'))
            ->with(['department:id,code,name', 'offerings' => fn ($o) => $semester
                ? $this->publicOfferings($o, $semester)->with($this->sectionRelations())
                : $o->whereRaw('false')])
            ->orderBy('code')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /** A public course, or null (draft, archived, or its department inactive). */
    public function find(int $id): ?Course
    {
        $semester = $this->semester();

        return $this->publicCourses()
            ->with([
                'department:id,code,name',
                'prerequisites' => fn ($q) => $q->where('status', Course::STATUS_ACTIVE)->orderBy('code'),
                'programs' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
                'offerings' => fn ($o) => $semester ? $this->publicOfferings($o, $semester)->with($this->sectionRelations()) : $o->whereRaw('false'),
            ])
            ->find($id);
    }

    /**
     * The public shape of one course; `$detail` adds prerequisites, programs
     * and this semester's sections.
     *
     * @return array<string, mixed>
     */
    public function present(Course $course, bool $detail = false): array
    {
        $sections = $course->offerings->flatMap(fn (CourseOffering $offering) => $offering->sections)->sortBy('code')->values();
        $lecturers = $this->showLecturers()
            ? $sections->flatMap(fn (Section $section) => $section->lecturers)->unique('id')->map(fn (Lecturer $l) => $l->fullName())->values()->all()
            : [];

        $data = [
            'id' => $course->id,
            'code' => $course->code,
            'name' => $course->name,
            'credits' => (float) $course->credits,
            'course_level' => $course->course_level,
            'description' => $course->description,
            'department' => $course->department ? ['id' => $course->department->id, 'code' => $course->department->code, 'name' => $course->department->name] : null,
            'offered' => $sections->isNotEmpty(),
            'lecturers' => $lecturers,
        ];

        if (! $detail) {
            return $data;
        }

        return [
            ...$data,
            'lecture_hours' => $course->lecture_hours === null ? null : (float) $course->lecture_hours,
            'lab_hours' => $course->lab_hours === null ? null : (float) $course->lab_hours,
            'prerequisites' => $course->prerequisites->map(fn (Course $p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'is_strict' => (bool) $p->pivot->is_strict])->values()->all(),
            'programs' => $course->programs->map(fn ($p) => ['code' => $p->code, 'name' => $p->name, 'is_required' => (bool) $p->pivot->is_required])->values()->all(),
            'sections' => $sections->map(fn (Section $section) => [
                'code' => $section->code,
                'lecturers' => $this->showLecturers() ? $section->lecturers->map(fn (Lecturer $l) => $l->fullName())->values()->all() : [],
                'meetings' => $section->scheduleEntries->sortBy(fn (ScheduleEntry $e) => $e->day_of_week.$e->startsAt())->map(fn (ScheduleEntry $e) => [
                    'day' => ScheduleEntry::DAYS[$e->day_of_week],
                    'start_time' => $e->startsAt(),
                    'end_time' => $e->endsAt(),
                    'room' => $e->room?->code,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array{id: int, name: string, status: string}|null
     */
    public function presentSemester(?Semester $semester): ?array
    {
        return $semester ? ['id' => $semester->id, 'name' => trim(($semester->academicYear?->code ?? '').' '.$semester->name), 'status' => $semester->status->value] : null;
    }

    public function showLecturers(): bool
    {
        return (bool) config('academics.catalog.show_lecturers', true);
    }

    /** @return Builder<Course> */
    private function publicCourses(): Builder
    {
        return Course::query()
            ->where('status', Course::STATUS_ACTIVE)
            ->whereHas('department', fn (Builder $d) => $d->where('is_active', true));
    }

    private function publicOfferings($query, Semester $semester)
    {
        return $query->where('semester_id', $semester->getKey())
            ->whereIn('status', self::PUBLIC_OFFERINGS)
            ->whereHas('sections', fn (Builder $s) => $s->whereIn('status', self::PUBLIC_SECTIONS));
    }

    /** @return array<string, \Closure> */
    private function sectionRelations(): array
    {
        return ['sections' => fn ($s) => $s->whereIn('status', self::PUBLIC_SECTIONS)->with(['lecturers', 'scheduleEntries.room:id,code'])];
    }
}
