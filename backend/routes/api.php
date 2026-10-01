<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ErrorLogController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\LecturerController;
use App\Http\Controllers\Api\ProgramController;
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
});

// A student may read their own profile; `StudentPolicy::view` limits them to it.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,faculty-admin,student'])->group(function () {
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('api.students.show');
});

// A lecturer may read their own profile; `LecturerPolicy::view` limits them to it.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,faculty-admin,lecturer'])->group(function () {
    Route::get('/lecturers/{lecturer}', [LecturerController::class, 'show'])->name('api.lecturers.show');
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
