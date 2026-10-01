<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\ErrorLogController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\SemesterController;
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
