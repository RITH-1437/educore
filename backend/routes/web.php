<?php

use App\Enums\Role;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AnalyticsPageController;
use App\Http\Controllers\AnnouncementsController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseMaterialsController;
use App\Http\Controllers\CourseOfferingController;
use App\Http\Controllers\CourseworkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\DocumentTypesController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\ExamsController;
use App\Http\Controllers\GradesController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\InternshipsController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\NotificationPreferencesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use App\Services\LandingStatsService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (LandingStatsService $landing) {
    if (! auth()->check()) {
        // Live, aggregate-only figures (no personal data) — empty database, empty figures.
        return Inertia::render('Landing', ['stats' => $landing->build()]);
    }

    return auth()->user()->isRole(Role::SuperAdmin->value)
        ? redirect()->route('admin.dashboard')
        : redirect()->route('role-dashboard');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    // Password reset (`skills/authentication`): generic answers, rate limited.
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:password-reset')->name('password.update');
});

// User profile portal and password change (every signed-in role).
Route::middleware('auth')->group(function () {
    Route::get('/account/profile', [ProfileController::class, 'show'])->name('account.profile');
    Route::put('/account/profile', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::permanentRedirect('/profile', '/account/profile');
    Route::get('/account/password', [PasswordController::class, 'edit'])->name('account.password');
    Route::put('/account/password', [PasswordController::class, 'update'])->name('account.password.update');
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

Route::middleware(['auth', 'role:university-admin,department-admin,lecturer,student'])
    ->get('/dashboard', [DashboardController::class, 'roleDashboard'])
    ->name('role-dashboard');

// Reading the university structure is open to Department Admin as well; changing
// it is university-wide data and stays with Super Admin / University Admin.
// `UniversityPolicy` and `DepartmentPolicy` mirror this split.
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])
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

// The faculty level was removed (report 39): old bookmarks land on departments
// instead of a 404 in the error log.
Route::permanentRedirect('/faculties/{path?}', '/departments')->where('path', '.*')->name('faculties.moved');

Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])
    ->prefix('/departments')
    ->name('departments.')
    ->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:super-admin,university-admin'])
    ->prefix('/departments')
    ->name('departments.')
    ->group(function () {
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
        Route::get('/{department}/edit', [DepartmentController::class, 'edit'])->name('edit');
        Route::put('/{department}', [DepartmentController::class, 'update'])->name('update');
        Route::post('/{department}/archive', [DepartmentController::class, 'archive'])->name('archive');
        Route::post('/{department}/reactivate', [DepartmentController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{department}', [DepartmentController::class, 'destroy'])->name('destroy');
    });

// Programs follow the same split as the rest of the structure: Department Admin
// may read, only Super Admin / University Admin may change (`ProgramPolicy`).
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])
    ->prefix('/programs')
    ->name('programs.')
    ->group(function () {
        Route::get('/', [ProgramController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:super-admin,university-admin'])
    ->prefix('/programs')
    ->name('programs.')
    ->group(function () {
        Route::post('/', [ProgramController::class, 'store'])->name('store');
        Route::get('/{program}/edit', [ProgramController::class, 'edit'])->name('edit');
        Route::put('/{program}', [ProgramController::class, 'update'])->name('update');
        Route::post('/{program}/archive', [ProgramController::class, 'archive'])->name('archive');
        Route::post('/{program}/reactivate', [ProgramController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{program}', [ProgramController::class, 'destroy'])->name('destroy');

        // Curriculum: course membership nests under the program that owns it.
        Route::post('/{program}/courses', [ProgramController::class, 'addCourse'])->name('courses.store');
        Route::put('/{program}/courses/{course}', [ProgramController::class, 'updateCourse'])->name('courses.update');
        Route::delete('/{program}/courses/{course}', [ProgramController::class, 'removeCourse'])->name('courses.destroy');
    });

// Attendance: lecturers take it for their sections; staff open the register
// from the offering page; students see their own (`AttendancePolicy`).
Route::middleware(['auth', 'role:lecturer'])->get('/attendance', [AttendanceController::class, 'classes'])->name('attendance.classes');
Route::middleware(['auth', 'role:student'])->get('/my-attendance', [AttendanceController::class, 'mine'])->name('attendance.mine');
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin,lecturer'])->group(function () {
    Route::get('/attendance/sections/{section}', [AttendanceController::class, 'section'])->name('attendance.section');
    Route::post('/attendance/sections/{section}', [AttendanceController::class, 'record'])->name('attendance.record');
    Route::post('/attendance-sessions/{session}/cancel', [AttendanceController::class, 'cancel'])->name('attendance.cancel');
});

// Coursework (assignments): one page per section, scoped by `AssignmentPolicy`
// (lecturers of the section, staff, enrolled students); students also get an
// overview of their own assignments.
Route::middleware(['auth', 'role:student'])->get('/my-assignments', [CourseworkController::class, 'mine'])->name('coursework.mine');
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin,lecturer,student'])->group(function () {
    Route::get('/coursework/sections/{section}', [CourseworkController::class, 'section'])->name('coursework.section');
    Route::post('/coursework/sections/{section}', [CourseworkController::class, 'store'])->name('coursework.store');
    Route::put('/assignments/{assignment}', [CourseworkController::class, 'update'])->name('assignments.update');
    Route::post('/assignments/{assignment}/publish', [CourseworkController::class, 'publish'])->name('assignments.publish');
    Route::delete('/assignments/{assignment}', [CourseworkController::class, 'destroy'])->name('assignments.destroy');
    Route::post('/assignments/{assignment}/submit', [CourseworkController::class, 'submit'])->name('assignments.submit');
    Route::post('/submissions/{submission}/grade', [CourseworkController::class, 'grade'])->name('submissions.grade');
    Route::get('/submissions/{submission}/file', [CourseworkController::class, 'download'])->name('submissions.file');
});

// Course materials (report 44): shared on the section's coursework page
// (`CourseMaterialPolicy`); students find their courses' materials together.
Route::middleware(['auth', 'role:student'])->get('/my-materials', [CourseMaterialsController::class, 'mine'])->name('materials.mine');
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin,lecturer,student'])->group(function () {
    Route::post('/coursework/sections/{section}/materials', [CourseMaterialsController::class, 'store'])->name('materials.store');
    Route::put('/materials/{material}', [CourseMaterialsController::class, 'update'])->name('materials.update');
    Route::delete('/materials/{material}', [CourseMaterialsController::class, 'destroy'])->name('materials.destroy');
    Route::get('/materials/{material}/file', [CourseMaterialsController::class, 'download'])->name('materials.file');
});

// Examinations: one page per section, scoped by `ExamPolicy` (lecturers of the
// section and managers write, Department Admin reads, enrolled students see the
// schedule and released results); students also get an overview.
Route::middleware(['auth', 'role:student'])->get('/my-exams', [ExamsController::class, 'mine'])->name('exams.mine');
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin,lecturer,student'])->group(function () {
    Route::get('/exams/sections/{section}', [ExamsController::class, 'section'])->name('exams.section');
    Route::post('/exams/sections/{section}', [ExamsController::class, 'store'])->name('exams.store');
    Route::put('/exams/{exam}', [ExamsController::class, 'update'])->name('exams.update');
    Route::delete('/exams/{exam}', [ExamsController::class, 'destroy'])->name('exams.destroy');
    Route::post('/exams/{exam}/publish', [ExamsController::class, 'publish'])->name('exams.publish');
    Route::post('/exams/{exam}/results', [ExamsController::class, 'record'])->name('exams.results');
});

// Grades & GPA (`GradePolicy`): lecturers of the section compute and submit,
// managers approve / return and edit the scale and course weights, Department
// Admin reads, students see their own approved grades and GPA.
Route::middleware(['auth', 'role:student'])->get('/my-grades', [GradesController::class, 'mine'])->name('grades.mine');
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])->get('/grades', [GradesController::class, 'index'])->name('grades.index');
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin,lecturer'])->group(function () {
    Route::get('/grades/sections/{section}', [GradesController::class, 'section'])->name('grades.section');
    Route::post('/grades/sections/{section}', [GradesController::class, 'compute'])->name('grades.compute');
    Route::post('/grades/sections/{section}/submit', [GradesController::class, 'submit'])->name('grades.submit');
    Route::get('/grading-scale', [GradesController::class, 'scale'])->name('grading-scale');
});
Route::middleware(['auth', 'role:super-admin,university-admin'])->group(function () {
    Route::post('/grades/sections/{section}/approve', [GradesController::class, 'approve'])->name('grades.approve');
    Route::post('/grades/sections/{section}/return', [GradesController::class, 'returnToDraft'])->name('grades.return');
    Route::post('/grades/sections/{section}/finalize', [GradesController::class, 'finalize'])->name('grades.finalize');
    Route::post('/grades/sections/{section}/reopen', [GradesController::class, 'reopen'])->name('grades.reopen');
    Route::put('/grading-scale', [GradesController::class, 'updateScale'])->name('grading-scale.update');
    Route::put('/courses/{course}/grading-config', [GradesController::class, 'updateConfig'])->name('courses.grading-config.update');
});

// Documents (`DocumentRequestPolicy`): students request and download their
// own; managers and (for their department's students) Department Admins approve /
// reject / generate; only managers revoke.
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/my-documents', [DocumentsController::class, 'mine'])->name('documents.mine');
    Route::post('/my-documents', [DocumentsController::class, 'store'])->name('documents.store');
});
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])->get('/documents', [DocumentsController::class, 'index'])->name('documents.index');
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])->group(function () {
    Route::post('/document-requests/{documentRequest}/approve', [DocumentsController::class, 'approve'])->name('documents.approve');
    Route::post('/document-requests/{documentRequest}/reject', [DocumentsController::class, 'reject'])->name('documents.reject');
    Route::post('/document-requests/{documentRequest}/generate', [DocumentsController::class, 'generate'])->name('documents.generate');
});
Route::middleware(['auth', 'role:super-admin,university-admin'])->group(function () {
    Route::post('/documents/{document}/revoke', [DocumentsController::class, 'revoke'])->name('documents.revoke');
    Route::post('/document-requests/{documentRequest}/waive-fee', [DocumentsController::class, 'waiveFee'])->name('documents.waive-fee');
});
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin,student'])->get('/documents/{document}/download', [DocumentsController::class, 'download'])->name('documents.download');

// Document Types management (`DocumentTypePolicy`): Super Admin and University Admin
Route::middleware(['auth', 'role:super-admin,university-admin'])->group(function () {
    Route::get('/document-types', [DocumentTypesController::class, 'index'])->name('document-types.index');
    Route::post('/document-types', [DocumentTypesController::class, 'store'])->name('document-types.store');
    Route::put('/document-types/{documentType}', [DocumentTypesController::class, 'update'])->name('document-types.update');
    Route::delete('/document-types/{documentType}', [DocumentTypesController::class, 'destroy'])->name('document-types.destroy');
});

// Finance (`InvoicePolicy`): managers manage invoices and payment records; a
// student reads their own.
Route::middleware(['auth', 'role:student'])->get('/my-invoices', [InvoicesController::class, 'mine'])->name('invoices.mine');
Route::middleware(['auth', 'role:super-admin,university-admin'])->group(function () {
    Route::get('/invoices', [InvoicesController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/export', [ExportController::class, 'invoices'])->name('invoices.export');
    Route::get('/invoices/create', [InvoicesController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoicesController::class, 'store'])->name('invoices.store');
    Route::post('/invoices/generate-tuition', [InvoicesController::class, 'generateTuition'])->name('invoices.generate-tuition');
    Route::get('/invoices/{invoice}/edit', [InvoicesController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoicesController::class, 'update'])->name('invoices.update');
    Route::post('/invoices/{invoice}/cancel', [InvoicesController::class, 'cancel'])->name('invoices.cancel');
    Route::post('/invoices/{invoice}/payments', [InvoicesController::class, 'pay'])->name('invoices.payments.store');
    Route::post('/payments/{payment}/reverse', [InvoicesController::class, 'reverse'])->name('payments.reverse');
});

Route::middleware(['auth', 'role:super-admin,university-admin,student'])->group(function () {
    Route::get('/invoices/{invoice}', [InvoicesController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [InvoicesController::class, 'download'])->name('invoices.download');
});

// Announcements: every signed-in role reads its feed; managers and lecturers
// compose (`AnnouncementPolicy` + audience rules in `AnnouncementService`).
Route::middleware('auth')->get('/announcements', [AnnouncementsController::class, 'feed'])->name('announcements.feed');
Route::middleware(['auth', 'role:super-admin,university-admin,lecturer'])->group(function () {
    Route::get('/announcements/manage', [AnnouncementsController::class, 'manage'])->name('announcements.manage');
    Route::post('/announcements', [AnnouncementsController::class, 'store'])->name('announcements.store');
    Route::put('/announcements/{announcement}', [AnnouncementsController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementsController::class, 'destroy'])->name('announcements.destroy');
    Route::post('/announcements/{announcement}/publish', [AnnouncementsController::class, 'publish'])->name('announcements.publish');
    Route::post('/announcements/{announcement}/archive', [AnnouncementsController::class, 'archive'])->name('announcements.archive');
});
Route::middleware('auth')->get('/announcements/{announcement}/attachments/{file}/download', [AnnouncementsController::class, 'downloadAttachment'])->name('announcements.attachments.download');

// Internships (`InternshipPolicy`): students apply and report; managers and
// (for their department's students) Department Admins review, approve and evaluate;
// only managers keep the companies.
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/my-internships', [InternshipsController::class, 'mine'])->name('internships.mine');
    Route::post('/my-internships', [InternshipsController::class, 'store'])->name('internships.store');
});
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])->group(function () {
    Route::get('/internships', [InternshipsController::class, 'index'])->name('internships.index');
    Route::get('/internship-companies', [InternshipsController::class, 'companies'])->name('internship-companies.index');
});
Route::middleware(['auth', 'role:super-admin,university-admin'])->group(function () {
    Route::post('/internship-companies', [InternshipsController::class, 'storeCompany'])->name('internship-companies.store');
    Route::put('/internship-companies/{company}', [InternshipsController::class, 'updateCompany'])->name('internship-companies.update');
});
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])->group(function () {
    Route::post('/internships/{internship}/evaluations', [InternshipsController::class, 'evaluate'])->name('internships.evaluate');
    Route::post('/internship-reports/{report}/review', [InternshipsController::class, 'reviewReport'])->name('internship-reports.review');
});
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin,student'])->group(function () {
    Route::get('/internships/{internship}', [InternshipsController::class, 'show'])->name('internships.show');
    Route::put('/internships/{internship}', [InternshipsController::class, 'update'])->name('internships.update');
    Route::post('/internships/{internship}/reports', [InternshipsController::class, 'report'])->name('internships.reports.store');
    Route::post('/internships/{internship}/{action}', [InternshipsController::class, 'transition'])->whereIn('action', ['submit', 'review', 'approve', 'reject', 'start', 'complete', 'cancel'])->name('internships.transition');
    Route::get('/internship-reports/{report}/file', [InternshipsController::class, 'downloadReport'])->name('internship-reports.file');
});

// Analytics (module 9.23): managers only.
Route::middleware(['auth', 'role:super-admin,university-admin'])->get('/analytics', AnalyticsPageController::class)->name('analytics');
Route::middleware(['auth', 'role:super-admin,university-admin'])->get('/analytics/export', [ExportController::class, 'analytics'])->name('analytics.export');
Route::middleware(['auth', 'role:super-admin,university-admin'])->get('/analytics/export/pdf', [ExportController::class, 'analyticsPdf'])->name('analytics.export.pdf');

// Notification settings (modules 9.20 / 9.21): every signed-in user, own only.
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationPreferencesController::class, 'edit'])->name('notifications.edit');
    Route::put('/notifications', [NotificationPreferencesController::class, 'update'])->name('notifications.update');
    Route::post('/notifications/test', [NotificationPreferencesController::class, 'test'])->middleware('throttle:notification-test')->name('notifications.test');
});

// In-app inbox (report 42): every signed-in user, own notifications only.
Route::middleware('auth')->prefix('/inbox')->name('inbox.')->group(function () {
    Route::get('/', [InboxController::class, 'index'])->name('index');
    Route::post('/read-all', [InboxController::class, 'readAll'])->name('read-all');
    Route::post('/{notification}/open', [InboxController::class, 'open'])->whereUuid('notification')->name('open');
    Route::post('/{notification}/read', [InboxController::class, 'read'])->whereUuid('notification')->name('read');
});

// Public verification page (module 9.17) — no sign-in.
Route::get('/verify/{token}', [DocumentsController::class, 'verify'])->middleware('throttle:verification')->name('documents.verify');

// Timetable: rooms (staff read, managers write), section schedules (managers),
// and a personal weekly timetable for students and lecturers.
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])->group(function () {
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
});

Route::middleware(['auth', 'role:super-admin,university-admin'])->group(function () {
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
    Route::post('/sections/{section}/schedule', [TimetableController::class, 'store'])->name('sections.schedule.store');
    Route::delete('/schedule-entries/{entry}', [TimetableController::class, 'destroy'])->name('schedule-entries.destroy');
});

Route::middleware(['auth', 'role:student,lecturer'])->get('/timetable', [TimetableController::class, 'mine'])->name('timetable.mine');

// Enrollment management: Department Admin reads, managers enroll/drop/complete
// (`EnrollmentPolicy`). Student self-service lives under /registration.
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])->group(function () {
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/export', [ExportController::class, 'enrollments'])->name('enrollments.export');
});

Route::middleware(['auth', 'role:super-admin,university-admin'])->group(function () {
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::post('/enrollments/{enrollment}/drop', [EnrollmentController::class, 'drop'])->name('enrollments.drop');
    Route::post('/enrollments/{enrollment}/complete', [EnrollmentController::class, 'complete'])->name('enrollments.complete');
});

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/registration', [RegistrationController::class, 'index'])->name('registration.index');
    Route::post('/registration', [RegistrationController::class, 'store'])->name('registration.store');
    Route::post('/registration/{enrollment}/drop', [RegistrationController::class, 'drop'])->name('registration.drop');
});

// Offerings & sections: Department Admin reads, Super Admin / University Admin
// manage (`CourseOfferingPolicy`).
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])->group(function () {
    Route::get('/offerings', [CourseOfferingController::class, 'index'])->name('offerings.index');
    Route::get('/offerings/{offering}', [CourseOfferingController::class, 'show'])->name('offerings.show');
});

Route::middleware(['auth', 'role:super-admin,university-admin'])->group(function () {
    Route::post('/offerings', [CourseOfferingController::class, 'store'])->name('offerings.store');
    Route::put('/offerings/{offering}', [CourseOfferingController::class, 'update'])->name('offerings.update');
    Route::delete('/offerings/{offering}', [CourseOfferingController::class, 'destroy'])->name('offerings.destroy');
    Route::post('/offerings/{offering}/sections', [CourseOfferingController::class, 'storeSection'])->name('offerings.sections.store');
    Route::put('/sections/{section}', [CourseOfferingController::class, 'updateSection'])->name('sections.update');
    Route::delete('/sections/{section}', [CourseOfferingController::class, 'destroySection'])->name('sections.destroy');
    Route::post('/sections/{section}/lecturers', [CourseOfferingController::class, 'assignLecturer'])->name('sections.lecturers.store');
    Route::delete('/sections/{section}/lecturers/{lecturer}', [CourseOfferingController::class, 'removeLecturer'])->name('sections.lecturers.destroy');
});

// Students: Department Admin reads, Super Admin / University Admin manage
// (`StudentPolicy`).
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])
    ->prefix('/students')
    ->name('students.')
    ->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:super-admin,university-admin'])
    ->prefix('/students')
    ->name('students.')
    ->group(function () {
        Route::post('/', [StudentController::class, 'store'])->name('store');
        Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('edit');
        Route::put('/{student}', [StudentController::class, 'update'])->name('update');
        Route::post('/{student}/status', [StudentController::class, 'changeStatus'])->name('status');
        Route::post('/{student}/program', [StudentController::class, 'changeProgram'])->name('program');
        Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
    });

// Lecturers: Department Admin reads, Super Admin / University Admin manage
// (`LecturerPolicy`).
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])
    ->prefix('/lecturers')
    ->name('lecturers.')
    ->group(function () {
        Route::get('/', [LecturerController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:super-admin,university-admin'])
    ->prefix('/lecturers')
    ->name('lecturers.')
    ->group(function () {
        Route::post('/', [LecturerController::class, 'store'])->name('store');
        Route::get('/{lecturer}/edit', [LecturerController::class, 'edit'])->name('edit');
        Route::put('/{lecturer}', [LecturerController::class, 'update'])->name('update');
        Route::post('/{lecturer}/deactivate', [LecturerController::class, 'deactivate'])->name('deactivate');
        Route::post('/{lecturer}/reactivate', [LecturerController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{lecturer}', [LecturerController::class, 'destroy'])->name('destroy');
    });

// Courses follow the same split: Department Admin reads, Super Admin / University
// Admin change (`CoursePolicy`).
Route::middleware(['auth', 'role:super-admin,university-admin,department-admin'])
    ->prefix('/courses')
    ->name('courses.')
    ->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('index');
    });

Route::middleware(['auth', 'role:super-admin,university-admin'])
    ->prefix('/courses')
    ->name('courses.')
    ->group(function () {
        Route::post('/', [CourseController::class, 'store'])->name('store');
        Route::get('/{course}/edit', [CourseController::class, 'edit'])->name('edit');
        Route::put('/{course}', [CourseController::class, 'update'])->name('update');
        Route::post('/{course}/archive', [CourseController::class, 'archive'])->name('archive');
        Route::post('/{course}/reactivate', [CourseController::class, 'reactivate'])->name('reactivate');
        Route::delete('/{course}', [CourseController::class, 'destroy'])->name('destroy');
        Route::post('/{course}/prerequisites', [CourseController::class, 'addPrerequisite'])->name('prerequisites.store');
        Route::delete('/{course}/prerequisites/{prerequisite}', [CourseController::class, 'removePrerequisite'])->name('prerequisites.destroy');
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

// Audit trail (module 9.24): Super Admin only, read-only — no write routes.
Route::middleware(['auth', 'role:super-admin'])->prefix('/audit-logs')->name('audit-logs.')->group(function () {
    Route::get('/', [AuditLogsController::class, 'index'])->name('index');
    Route::get('/export', [ExportController::class, 'auditLogs'])->name('export');
    Route::get('/{auditLog}', [AuditLogsController::class, 'show'])->name('show');
});
