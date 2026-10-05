<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Semester;
use App\Models\User;
use App\Services\DepartmentDashboardService;
use App\Services\LecturerDashboardService;
use App\Services\StudentDashboardService;
use App\Services\UniversityDashboardService;
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
            'total_departments' => DB::table('departments')->count(),
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

    public function roleDashboard(
        Request $request,
        StudentDashboardService $students,
        DepartmentDashboardService $departments,
        LecturerDashboardService $lecturers,
        UniversityDashboardService $university,
    ): Response {
        $user = $request->user();

        // Students get their academic dashboard (module 9.15); no linked profile = no academic data.
        if ($user->isRole('student')) {
            return Inertia::render('Student/Dashboard', [
                'dashboard' => $user->student ? $students->build($user->student) : null,
                'userName' => $user->name,
            ]);
        }

        // Department Admins get their department's dashboard (report 39); none assigned = no unit data.
        if ($user->isRole('department-admin')) {
            return Inertia::render('DepartmentAdmin/Dashboard', [
                'dashboard' => $user->department ? $departments->build($user->department) : null,
                'userName' => $user->name,
            ]);
        }

        // Lecturers get their teaching dashboard (report 35); no linked profile = no teaching data.
        if ($user->isRole('lecturer')) {
            return Inertia::render('Lecturer/Dashboard', [
                'dashboard' => $user->lecturer ? $lecturers->build($user->lecturer) : null,
                'userName' => $user->name,
            ]);
        }

        // University Admins get their institution-wide dashboard (report 36).
        if ($user->isRole('university-admin')) {
            return Inertia::render('UniversityAdmin/Dashboard', [
                'dashboard' => $university->build(),
                'userName' => $user->name,
            ]);
        }

        // The route admits only the four roles above.
        abort(403);
    }
}
