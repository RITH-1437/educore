<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SemesterController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
