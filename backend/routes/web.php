<?php

use App\Enums\Role;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (! auth()->check()) {
        return Inertia::render('Landing');
    }

    return auth()->user()->isRole(Role::SuperAdmin->value)
        ? redirect()->route('admin.dashboard')
        : redirect()->route('role-dashboard');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::middleware(['auth', 'role:university-admin,faculty-admin,lecturer,student'])
    ->get('/dashboard', [DashboardController::class, 'roleDashboard'])
    ->name('role-dashboard');

// Reading the university structure is open to Faculty Admin as well; changing
// it is university-wide data and stays with Super Admin / University Admin.
// `UniversityPolicy` and `FacultyPolicy` mirror this split.
Route::middleware(['auth', 'role:super-admin,university-admin,faculty-admin'])
    ->prefix('/universities')
    ->name('universities.')
    ->group(function () {
        Route::get('/', [UniversityController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:super-admin,university-admin'])
    ->prefix('/universities')
    ->name('universities.')
    ->group(function () {
        Route::post('/', [UniversityController::class, 'store'])->name('store');
        Route::get('/{university}/edit', [UniversityController::class, 'edit'])->name('edit');
        Route::put('/{university}', [UniversityController::class, 'update'])->name('update');
        Route::post('/{university}/current', [UniversityController::class, 'makeCurrent'])->name('current');
        Route::delete('/{university}', [UniversityController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'role:super-admin,university-admin,faculty-admin'])
    ->prefix('/faculties')
    ->name('faculties.')
    ->group(function () {
        Route::get('/', [FacultyController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:super-admin,university-admin'])
    ->prefix('/faculties')
    ->name('faculties.')
    ->group(function () {
        Route::post('/', [FacultyController::class, 'store'])->name('store');
        Route::get('/{faculty}/edit', [FacultyController::class, 'edit'])->name('edit');
        Route::put('/{faculty}', [FacultyController::class, 'update'])->name('update');
        Route::post('/{faculty}/archive', [FacultyController::class, 'archive'])->name('archive');
        Route::post('/{faculty}/reactivate', [FacultyController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{faculty}', [FacultyController::class, 'destroy'])->name('destroy');

        // Departments are a genuine child collection, so they nest under the
        // faculty that owns them.
        Route::post('/{faculty}/departments', [FacultyController::class, 'storeDepartment'])
            ->name('departments.store');
        Route::put('/{faculty}/departments/{department}', [FacultyController::class, 'updateDepartment'])
            ->name('departments.update');
        Route::post('/{faculty}/departments/{department}/archive', [FacultyController::class, 'archiveDepartment'])
            ->name('departments.archive');
        Route::post('/{faculty}/departments/{department}/reactivate', [FacultyController::class, 'reactivateDepartment'])
            ->name('departments.reactivate');
        Route::delete('/{faculty}/departments/{department}', [FacultyController::class, 'destroyDepartment'])
            ->name('departments.destroy');
    });

// The academic calendar is university-wide data, so both admin roles reach it.
Route::middleware(['auth', 'role:super-admin,university-admin'])
    ->prefix('/academic-years')
    ->name('academic-years.')
    ->group(function () {
        Route::get('/', [AcademicYearController::class, 'index'])->name('index');
        Route::get('/create', [AcademicYearController::class, 'create'])->name('create');
        Route::post('/', [AcademicYearController::class, 'store'])->name('store');
        Route::get('/{academicYear}/edit', [AcademicYearController::class, 'edit'])->name('edit');
        Route::put('/{academicYear}', [AcademicYearController::class, 'update'])->name('update');
        Route::delete('/{academicYear}', [AcademicYearController::class, 'destroy'])->name('destroy');

        Route::post('/{academicYear}/status', [AcademicYearController::class, 'changeStatus'])->name('status');
        Route::post('/{academicYear}/current', [AcademicYearController::class, 'makeCurrent'])->name('current');

        Route::post('/{academicYear}/semesters', [AcademicYearController::class, 'storeSemester'])
            ->name('semesters.store');
        Route::post('/{academicYear}/semesters/{semester}/status', [AcademicYearController::class, 'changeSemesterStatus'])
            ->name('semesters.status');
        Route::delete('/{academicYear}/semesters/{semester}', [AcademicYearController::class, 'destroySemester'])
            ->name('semesters.destroy');
    });

// System error logs. Super Admin only — a row exposes an exception class and the
// originating path, which is enough to fingerprint internals (`ErrorLogPolicy`).
//
// Read-only by design and deliberately absent from the sidebar: it is a
// diagnostic tool reached by typing `/error-logs`, not a place you navigate to.
// Rows come from `ErrorLogRecorder`, which records 404 and 5xx only.
Route::middleware(['auth', 'role:super-admin'])
    ->prefix('/error-logs')
    ->name('error-logs.')
    ->group(function () {
        Route::get('/', [ErrorLogController::class, 'index'])->name('index');
        Route::get('/{errorLog}', [ErrorLogController::class, 'show'])->name('show');
    });
