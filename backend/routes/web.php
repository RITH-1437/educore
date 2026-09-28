<?php

use App\Enums\Role;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
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
