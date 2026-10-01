<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAdminDashboard', User::class);

        $currentYear = AcademicYear::query()
            ->with(['semesters' => fn ($query) => $query->orderBy('sequence')])
            ->where('is_current', true)
            ->first();

        $roleCounts = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereNull('users.deleted_at')
            ->select('roles.slug', 'roles.name', DB::raw('COUNT(users.id) AS total'))
            ->groupBy('roles.slug', 'roles.name')
            ->orderBy('roles.name')
            ->get()
            ->map(fn ($role) => [
                'slug' => $role->slug,
                'name' => $role->name,
                'total' => (int) $role->total,
            ]);

        $recentUsers = User::query()
            ->with('role')
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'is_active' => (bool) $user->is_active,
                'created_at' => $user->created_at?->toISOString(),
            ]);

        $stats = [
            'total_users' => User::query()->count(),
            'active_users' => User::query()->where('is_active', true)->count(),
            'total_roles' => Role::query()->count(),
            'total_academic_years' => AcademicYear::query()->count(),
            'active_academic_years' => AcademicYear::query()->where('status', 'active')->count(),
            'total_semesters' => Semester::query()->count(),
            'total_faculties' => DB::table('faculties')->count(),
            'total_programs' => DB::table('programs')->count(),
            'total_courses' => DB::table('courses')->count(),
            'total_lecturers' => DB::table('lecturers')->count(),
            'active_lecturers' => DB::table('lecturers')->where('is_active', true)->count(),
            'total_students' => DB::table('students')->count(),
            'active_students' => DB::table('students')->where('status', 'active')->count(),
            'total_offerings' => DB::table('course_offerings')->count(),
            'total_sections' => DB::table('sections')->count(),
            'attendance_sessions' => DB::table('attendance_sessions')->where('status', 'held')->count(),
            'open_enrollments' => DB::table('enrollments')->whereIn('status', ['pending', 'confirmed'])->whereNull('deleted_at')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'roleCounts' => $roleCounts,
            'currentAcademicYear' => $currentYear ? [
                'id' => $currentYear->id,
                'code' => $currentYear->code,
                'name' => $currentYear->name,
                'status' => $currentYear->status->value,
                'start_date' => $currentYear->start_date->toDateString(),
                'end_date' => $currentYear->end_date->toDateString(),
                'semesters' => $currentYear->semesters->map(fn (Semester $semester) => [
                    'id' => $semester->id,
                    'name' => $semester->name,
                    'sequence' => $semester->sequence,
                    'status' => $semester->status->value,
                    'start_date' => $semester->start_date?->toDateString(),
                    'end_date' => $semester->end_date?->toDateString(),
                ]),
            ] : null,
            'recentUsers' => $recentUsers,
        ]);
    }

    public function roleDashboard(Request $request): Response
    {
        $user = $request->user();

        $role = $user->role?->slug;
        $dashboard = match ($role) {
            'university-admin' => [
                'title' => 'University Admin Dashboard',
                'description' => 'A preview of university-wide academic operations and administration.',
                'areas' => ['Academic calendar', 'Faculties and departments', 'Programs and courses', 'Students and lecturers', 'Announcements and reports'],
            ],
            'faculty-admin' => [
                'title' => 'Faculty / Department Admin Dashboard',
                'description' => 'A preview of faculty and department administration.',
                'areas' => ['Departments', 'Programs and courses', 'Students and lecturers', 'Schedules and attendance'],
            ],
            'lecturer' => [
                'title' => 'Lecturer Dashboard',
                'description' => 'A preview of teaching, assessment, and course tools.',
                'areas' => ['My courses', 'Class schedule', 'Attendance', 'Assignments and exams', 'Grades and materials'],
            ],
            default => [
                'title' => 'Student Dashboard',
                'description' => 'A preview of personal academic information and student services.',
                'areas' => ['My courses', 'Timetable', 'Attendance', 'Grades and GPA', 'Documents and announcements'],
            ],
        };

        return Inertia::render('RoleDashboard', [
            ...$dashboard,
            'role' => $role,
            'userName' => $user->name,
        ]);
    }
}
