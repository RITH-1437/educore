<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseOfferingController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\ErrorLogController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\LecturerController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\SemesterController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UniversityController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('api.login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum')
    ->name('api.logout');

Route::middleware(['auth:sanctum', 'role:super-admin'])->group(function () {
    Route::apiResource('users', UserController::class, ['names' => 'api.users']);
});

// Read routes also allow Faculty Admin; write routes are university-wide data
// and stay with Super Admin / University Admin. `UniversityPolicy`,
// `FacultyPolicy` and `DepartmentPolicy` mirror this split.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,faculty-admin'])->group(function () {
    // Registered before `/faculties/{faculty}` so the literal path wins.
    Route::get('/faculties-tree', [DepartmentController::class, 'tree'])->name('api.faculties.tree');

    Route::get('/universities', [UniversityController::class, 'index'])->name('api.universities.index');
    Route::get('/universities/{university}', [UniversityController::class, 'show'])->name('api.universities.show');

    Route::get('/faculties', [FacultyController::class, 'index'])->name('api.faculties.index');
    Route::get('/faculties/{faculty}', [FacultyController::class, 'show'])->name('api.faculties.show');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('api.departments.index');
    Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('api.departments.show');

    Route::get('/programs', [ProgramController::class, 'index'])->name('api.programs.index');
    Route::get('/programs/{program}', [ProgramController::class, 'show'])->name('api.programs.show');

    Route::get('/courses', [CourseController::class, 'index'])->name('api.courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('api.courses.show');

    Route::get('/lecturers', [LecturerController::class, 'index'])->name('api.lecturers.index');
    Route::get('/students', [StudentController::class, 'index'])->name('api.students.index');

    Route::get('/offerings', [CourseOfferingController::class, 'index'])->name('api.offerings.index');
    Route::get('/offerings/{offering}', [CourseOfferingController::class, 'show'])->name('api.offerings.show');
    Route::get('/sections/{section}', [SectionController::class, 'show'])->name('api.sections.show');
    Route::get('/sections/{section}/schedule', [ScheduleController::class, 'index'])->name('api.sections.schedule');
    Route::get('/rooms', [RoomController::class, 'index'])->name('api.rooms.index');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('api.rooms.show');
});

// Enrollment: staff list everything; a student enrolls/drops/reads only their
// own (`EnrollmentPolicy`). Admin-only actions are checked by the policy.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,faculty-admin,student'])->group(function () {
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('api.enrollments.index');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('api.enrollments.store');
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show'])->name('api.enrollments.show');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('api.enrollments.destroy');
    Route::post('/enrollments/{enrollment}/complete', [EnrollmentController::class, 'complete'])->name('api.enrollments.complete');
    Route::get('/students/{student}/enrollments', [EnrollmentController::class, 'forStudent'])->name('api.students.enrollments');
    Route::get('/timetable/student/{student}', [ScheduleController::class, 'student'])->name('api.timetable.student');
});

// Attendance: `AttendancePolicy` decides per section / student (lecturer of the
// section, managers, Faculty Admin read-only, a student their own summary).
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,faculty-admin,lecturer,student'])->group(function () {
    Route::get('/sections/{section}/attendance', [AttendanceController::class, 'show'])->name('api.sections.attendance');
    Route::post('/sections/{section}/attendance', [AttendanceController::class, 'record'])->name('api.sections.attendance.store');
    Route::get('/sections/{section}/attendance/summary', [AttendanceController::class, 'summary'])->name('api.sections.attendance.summary');
    Route::post('/attendance-sessions/{session}/cancel', [AttendanceController::class, 'cancel'])->name('api.attendance-sessions.cancel');
    Route::get('/students/{student}/attendance', [AttendanceController::class, 'student'])->name('api.students.attendance');

    // Assignments: `AssignmentPolicy` scopes every call to the section's
    // lecturers / managers / enrolled students.
    Route::get('/sections/{section}/assignments', [AssignmentController::class, 'index'])->name('api.sections.assignments');
    Route::post('/sections/{section}/assignments', [AssignmentController::class, 'store'])->name('api.sections.assignments.store');
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('api.assignments.show');
    Route::match(['put', 'patch'], '/assignments/{assignment}', [AssignmentController::class, 'update'])->name('api.assignments.update');
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('api.assignments.destroy');
    Route::post('/assignments/{assignment}/publish', [AssignmentController::class, 'publish'])->name('api.assignments.publish');
    Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions'])->name('api.assignments.submissions');
    Route::post('/assignments/{assignment}/submissions', [AssignmentController::class, 'submit'])->name('api.assignments.submit');
    Route::post('/submissions/{submission}/grade', [AssignmentController::class, 'grade'])->name('api.submissions.grade');
    Route::get('/submissions/{submission}/file', [AssignmentController::class, 'download'])->name('api.submissions.file');
});

// A student may read their own profile; `StudentPolicy::view` limits them to it.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,faculty-admin,student'])->group(function () {
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('api.students.show');
});

// A lecturer may read their own profile; `LecturerPolicy::view` limits them to it.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,faculty-admin,lecturer'])->group(function () {
    Route::get('/lecturers/{lecturer}', [LecturerController::class, 'show'])->name('api.lecturers.show');
    Route::get('/lecturers/{lecturer}/sections', [LecturerController::class, 'sections'])->name('api.lecturers.sections');
    Route::get('/timetable/lecturer/{lecturer}', [ScheduleController::class, 'lecturer'])->name('api.timetable.lecturer');
});

Route::middleware(['auth:sanctum', 'role:super-admin,university-admin'])->group(function () {
    Route::post('/universities', [UniversityController::class, 'store'])->name('api.universities.store');
    Route::match(['put', 'patch'], '/universities/{university}', [UniversityController::class, 'update'])
        ->name('api.universities.update');
    Route::post('/universities/{university}/current', [UniversityController::class, 'makeCurrent'])
        ->name('api.universities.current');
    Route::delete('/universities/{university}', [UniversityController::class, 'destroy'])
        ->name('api.universities.destroy');

    Route::post('/faculties', [FacultyController::class, 'store'])->name('api.faculties.store');
    Route::match(['put', 'patch'], '/faculties/{faculty}', [FacultyController::class, 'update'])
        ->name('api.faculties.update');
    Route::post('/faculties/{faculty}/archive', [FacultyController::class, 'archive'])
        ->name('api.faculties.archive');
    Route::post('/faculties/{faculty}/reactivate', [FacultyController::class, 'reactivate'])
        ->name('api.faculties.reactivate');
    Route::delete('/faculties/{faculty}', [FacultyController::class, 'destroy'])
        ->name('api.faculties.destroy');

    Route::post('/departments', [DepartmentController::class, 'store'])->name('api.departments.store');
    Route::match(['put', 'patch'], '/departments/{department}', [DepartmentController::class, 'update'])
        ->name('api.departments.update');
    Route::post('/departments/{department}/archive', [DepartmentController::class, 'archive'])
        ->name('api.departments.archive');
    Route::post('/departments/{department}/reactivate', [DepartmentController::class, 'reactivate'])
        ->name('api.departments.reactivate');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
        ->name('api.departments.destroy');

    Route::post('/programs', [ProgramController::class, 'store'])->name('api.programs.store');
    Route::match(['put', 'patch'], '/programs/{program}', [ProgramController::class, 'update'])
        ->name('api.programs.update');
    Route::post('/programs/{program}/archive', [ProgramController::class, 'archive'])
        ->name('api.programs.archive');
    Route::post('/programs/{program}/reactivate', [ProgramController::class, 'reactivate'])
        ->name('api.programs.reactivate');
    Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])
        ->name('api.programs.destroy');

    // Curriculum: program <-> course membership lives with the program.
    Route::post('/programs/{program}/courses', [ProgramController::class, 'addCourse'])
        ->name('api.programs.courses.store');
    Route::patch('/programs/{program}/courses/{course}', [ProgramController::class, 'updateCourse'])
        ->name('api.programs.courses.update');
    Route::delete('/programs/{program}/courses/{course}', [ProgramController::class, 'removeCourse'])
        ->name('api.programs.courses.destroy');

    Route::post('/rooms', [RoomController::class, 'store'])->name('api.rooms.store');
    Route::match(['put', 'patch'], '/rooms/{room}', [RoomController::class, 'update'])->name('api.rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('api.rooms.destroy');
    Route::post('/sections/{section}/schedule', [ScheduleController::class, 'store'])->name('api.sections.schedule.store');
    Route::match(['put', 'patch'], '/schedule-entries/{entry}', [ScheduleController::class, 'update'])->name('api.schedule-entries.update');
    Route::delete('/schedule-entries/{entry}', [ScheduleController::class, 'destroy'])->name('api.schedule-entries.destroy');

    Route::post('/offerings', [CourseOfferingController::class, 'store'])->name('api.offerings.store');
    Route::match(['put', 'patch'], '/offerings/{offering}', [CourseOfferingController::class, 'update'])->name('api.offerings.update');
    Route::delete('/offerings/{offering}', [CourseOfferingController::class, 'destroy'])->name('api.offerings.destroy');
    Route::post('/offerings/{offering}/sections', [CourseOfferingController::class, 'storeSection'])->name('api.offerings.sections.store');
    Route::match(['put', 'patch'], '/sections/{section}', [SectionController::class, 'update'])->name('api.sections.update');
    Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('api.sections.destroy');
    Route::post('/sections/{section}/lecturers', [SectionController::class, 'assignLecturer'])->name('api.sections.lecturers.store');
    Route::delete('/sections/{section}/lecturers/{lecturer}', [SectionController::class, 'removeLecturer'])->name('api.sections.lecturers.destroy');

    Route::post('/students', [StudentController::class, 'store'])->name('api.students.store');
    Route::match(['put', 'patch'], '/students/{student}', [StudentController::class, 'update'])
        ->name('api.students.update');
    Route::post('/students/{student}/status', [StudentController::class, 'changeStatus'])
        ->name('api.students.status');
    Route::post('/students/{student}/program', [StudentController::class, 'changeProgram'])
        ->name('api.students.program');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])
        ->name('api.students.destroy');

    Route::post('/lecturers', [LecturerController::class, 'store'])->name('api.lecturers.store');
    Route::match(['put', 'patch'], '/lecturers/{lecturer}', [LecturerController::class, 'update'])
        ->name('api.lecturers.update');
    Route::post('/lecturers/{lecturer}/deactivate', [LecturerController::class, 'deactivate'])
        ->name('api.lecturers.deactivate');
    Route::post('/lecturers/{lecturer}/reactivate', [LecturerController::class, 'reactivate'])
        ->name('api.lecturers.reactivate');
    Route::delete('/lecturers/{lecturer}', [LecturerController::class, 'destroy'])
        ->name('api.lecturers.destroy');

    Route::post('/courses', [CourseController::class, 'store'])->name('api.courses.store');
    Route::match(['put', 'patch'], '/courses/{course}', [CourseController::class, 'update'])
        ->name('api.courses.update');
    Route::post('/courses/{course}/archive', [CourseController::class, 'archive'])
        ->name('api.courses.archive');
    Route::post('/courses/{course}/reactivate', [CourseController::class, 'reactivate'])
        ->name('api.courses.reactivate');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])
        ->name('api.courses.destroy');
    Route::post('/courses/{course}/prerequisites', [CourseController::class, 'addPrerequisite'])
        ->name('api.courses.prerequisites.store');
    Route::delete('/courses/{course}/prerequisites/{prerequisite}', [CourseController::class, 'removePrerequisite'])
        ->name('api.courses.prerequisites.destroy');
});

// The academic calendar is university-wide data, so both admin roles reach it.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin'])->group(function () {
    Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('api.academic-years.index');
    Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('api.academic-years.store');
    Route::get('/academic-years/{academicYear}', [AcademicYearController::class, 'show'])->name('api.academic-years.show');
    Route::match(['put', 'patch'], '/academic-years/{academicYear}', [AcademicYearController::class, 'update'])
        ->name('api.academic-years.update');
    Route::post('/academic-years/{academicYear}/status', [AcademicYearController::class, 'changeStatus'])
        ->name('api.academic-years.status');
    Route::delete('/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])
        ->name('api.academic-years.destroy');

    Route::get('/academic-years/{academicYear}/semesters', [SemesterController::class, 'index'])
        ->name('api.semesters.index');
    Route::post('/academic-years/{academicYear}/semesters', [SemesterController::class, 'store'])
        ->name('api.semesters.store');
    Route::post('/academic-years/{academicYear}/semesters/{semester}/status', [SemesterController::class, 'changeStatus'])
        ->name('api.semesters.status');
    Route::delete('/academic-years/{academicYear}/semesters/{semester}', [SemesterController::class, 'destroy'])
        ->name('api.semesters.destroy');
});

// System error logs: Super Admin only, read-only. Rows are recorded from 404
// and 5xx responses by `ErrorLogRecorder`, so there is no create, update or
// delete endpoint here on purpose (`skills/audit-logging/SKILL.md` §4).
Route::middleware(['auth:sanctum', 'role:super-admin'])->group(function () {
    Route::get('/error-logs', [ErrorLogController::class, 'index'])->name('api.error-logs.index');
    Route::get('/error-logs/{errorLog}', [ErrorLogController::class, 'show'])->name('api.error-logs.show');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
