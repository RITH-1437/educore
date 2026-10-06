<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseMaterialController;
use App\Http\Controllers\Api\CourseOfferingController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DepartmentDashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentTypeController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\ErrorLogController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\InternshipController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LecturerController;
use App\Http\Controllers\Api\LecturerDashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\SemesterController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentDashboardController;
use App\Http\Controllers\Api\UniversityController;
use App\Http\Controllers\Api\UniversityDashboardController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('api.login');

// Password reset (public, rate limited) and change (signed in).
Route::middleware('throttle:password-reset')->group(function () {
    Route::post('/forgot-password', [PasswordController::class, 'forgot'])->name('api.password.email');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->name('api.password.reset');
});
Route::put('/password', [PasswordController::class, 'update'])->middleware('auth:sanctum')->name('api.password.update');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum')
    ->name('api.logout');

Route::middleware(['auth:sanctum', 'role:super-admin'])->group(function () {
    Route::apiResource('users', UserController::class, ['names' => 'api.users']);
});

// Read routes also allow Department Admin; write routes are university-wide data
// and stay with Super Admin / University Admin. `UniversityPolicy` and
// `DepartmentPolicy` mirror this split.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,department-admin'])->group(function () {
    Route::get('/universities', [UniversityController::class, 'index'])->name('api.universities.index');
    Route::get('/universities/{university}', [UniversityController::class, 'show'])->name('api.universities.show');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('api.departments.index');
    Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('api.departments.show');
    // Waiting requests and headline numbers; `DepartmentPolicy::view` keeps a Department Admin to their own.
    Route::get('/departments/{department}/dashboard', DepartmentDashboardController::class)->name('api.departments.dashboard');

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
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,department-admin,student'])->group(function () {
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('api.enrollments.index');
    Route::get('/enrollments/export', [ExportController::class, 'enrollments'])->name('api.enrollments.export');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('api.enrollments.store');
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show'])->name('api.enrollments.show');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('api.enrollments.destroy');
    Route::post('/enrollments/{enrollment}/complete', [EnrollmentController::class, 'complete'])->name('api.enrollments.complete');
    Route::get('/students/{student}/enrollments', [EnrollmentController::class, 'forStudent'])->name('api.students.enrollments');
    Route::get('/timetable/student/{student}', [ScheduleController::class, 'student'])->name('api.timetable.student');
});

// Attendance: `AttendancePolicy` decides per section / student (lecturer of the
// section, managers, Department Admin read-only, a student their own summary).
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,department-admin,lecturer,student'])->group(function () {
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

    // Course materials (report 44): `CourseMaterialPolicy` — the section's
    // lecturers / managers share, its staff and enrolled students read.
    Route::get('/sections/{section}/materials', [CourseMaterialController::class, 'index'])->name('api.sections.materials');
    Route::post('/sections/{section}/materials', [CourseMaterialController::class, 'store'])->name('api.sections.materials.store');
    Route::match(['put', 'patch'], '/materials/{material}', [CourseMaterialController::class, 'update'])->name('api.materials.update');
    Route::delete('/materials/{material}', [CourseMaterialController::class, 'destroy'])->name('api.materials.destroy');
    Route::get('/materials/{material}/file', [CourseMaterialController::class, 'download'])->name('api.materials.file');

    // Examinations: `ExamPolicy` scopes every call to the section's lecturers /
    // managers (write), Department Admin (read) and enrolled students (schedule +
    // released results).
    Route::get('/sections/{section}/exams', [ExamController::class, 'index'])->name('api.sections.exams');
    Route::post('/sections/{section}/exams', [ExamController::class, 'store'])->name('api.sections.exams.store');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('api.exams.show');
    Route::match(['put', 'patch'], '/exams/{exam}', [ExamController::class, 'update'])->name('api.exams.update');
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('api.exams.destroy');
    Route::post('/exams/{exam}/publish', [ExamController::class, 'publish'])->name('api.exams.publish');
    Route::post('/exams/{exam}/results', [ExamController::class, 'record'])->name('api.exams.results');
    Route::patch('/exam-results/{result}', [ExamController::class, 'correct'])->name('api.exam-results.correct');
    Route::get('/students/{student}/exams', [ExamController::class, 'student'])->name('api.students.exams');

    // Grades & GPA: `GradePolicy` — lecturers of the section compute / submit,
    // managers approve / return and configure, Department Admin reads, a student
    // reads their own grades and GPA.
    Route::get('/sections/{section}/grades', [GradeController::class, 'sheet'])->name('api.sections.grades');
    Route::post('/sections/{section}/grades', [GradeController::class, 'compute'])->name('api.sections.grades.compute');
    Route::post('/sections/{section}/grades/submit', [GradeController::class, 'submit'])->name('api.sections.grades.submit');
    Route::post('/sections/{section}/grades/approve', [GradeController::class, 'approve'])->name('api.sections.grades.approve');
    Route::post('/sections/{section}/grades/return', [GradeController::class, 'returnToDraft'])->name('api.sections.grades.return');
    Route::post('/sections/{section}/grades/finalize', [GradeController::class, 'finalize'])->name('api.sections.grades.finalize');
    Route::post('/sections/{section}/grades/reopen', [GradeController::class, 'reopen'])->name('api.sections.grades.reopen');
    Route::get('/students/{student}/grades', [GradeController::class, 'student'])->name('api.students.grades');
    Route::get('/students/{student}/gpa', [GradeController::class, 'gpa'])->name('api.students.gpa');
    Route::get('/grading-scale', [GradeController::class, 'scale'])->name('api.grading-scale');
    Route::put('/grading-scale', [GradeController::class, 'updateScale'])->name('api.grading-scale.update');
    Route::get('/courses/{course}/grading-config', [GradeController::class, 'config'])->name('api.courses.grading-config');
    Route::put('/courses/{course}/grading-config', [GradeController::class, 'updateConfig'])->name('api.courses.grading-config.update');

    // Documents: `DocumentRequestPolicy` — a student requests and downloads
    // their own, managers process, Department Admin reads.
    Route::get('/document-types', [DocumentController::class, 'types'])->name('api.document-types');
    Route::post('/document-types', [DocumentTypeController::class, 'store'])->name('api.document-types.store');
    Route::get('/document-types/{documentType}', [DocumentTypeController::class, 'show'])->name('api.document-types.show');
    Route::match(['put', 'patch'], '/document-types/{documentType}', [DocumentTypeController::class, 'update'])->name('api.document-types.update');
    Route::delete('/document-types/{documentType}', [DocumentTypeController::class, 'destroy'])->name('api.document-types.destroy');
    Route::get('/document-requests', [DocumentController::class, 'index'])->name('api.document-requests.index');
    Route::post('/document-requests', [DocumentController::class, 'store'])->name('api.document-requests.store');
    Route::get('/document-requests/{documentRequest}', [DocumentController::class, 'show'])->name('api.document-requests.show');
    Route::post('/document-requests/{documentRequest}/approve', [DocumentController::class, 'approve'])->name('api.document-requests.approve');
    Route::post('/document-requests/{documentRequest}/reject', [DocumentController::class, 'reject'])->name('api.document-requests.reject');
    Route::post('/document-requests/{documentRequest}/generate', [DocumentController::class, 'generate'])->name('api.document-requests.generate');
    Route::post('/document-requests/{documentRequest}/waive-fee', [DocumentController::class, 'waiveFee'])->name('api.document-requests.waive-fee');
    Route::post('/documents/{document}/revoke', [DocumentController::class, 'revoke'])->name('api.documents.revoke');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('api.documents.download');

    // Finance: `InvoicePolicy` — managers only (no Finance Officer role); a
    // student reads their own invoices.
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('api.invoices.index');
    Route::get('/invoices/export', [ExportController::class, 'invoices'])->name('api.invoices.export');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('api.invoices.store');
    Route::post('/invoices/generate-tuition', [InvoiceController::class, 'generateTuition'])->name('api.invoices.generate-tuition');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('api.invoices.show');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('api.invoices.download');
    Route::match(['put', 'patch'], '/invoices/{invoice}', [InvoiceController::class, 'update'])->name('api.invoices.update');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('api.invoices.cancel');
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'pay'])->name('api.invoices.payments.store');
    Route::post('/payments/{payment}/reverse', [InvoiceController::class, 'reverse'])->name('api.payments.reverse');
    Route::get('/students/{student}/invoices', [InvoiceController::class, 'student'])->name('api.students.invoices');

    // Announcements: every role reads its feed; `AnnouncementPolicy` limits
    // writing to managers and active lecturers (own sections / courses).
    Route::get('/announcements/feed', [AnnouncementController::class, 'feed'])->name('api.announcements.feed');
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('api.announcements.index');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('api.announcements.store');
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->name('api.announcements.show');
    Route::match(['put', 'patch'], '/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('api.announcements.update');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('api.announcements.destroy');
    Route::post('/announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('api.announcements.publish');
    Route::post('/announcements/{announcement}/archive', [AnnouncementController::class, 'archive'])->name('api.announcements.archive');
    Route::get('/announcements/{announcement}/attachments/{file}/download', [AnnouncementController::class, 'downloadAttachment'])->name('api.announcements.attachments.download');

    // Internships: `InternshipPolicy` — a student runs their own application
    // and reports; managers review, approve, evaluate and keep companies.
    Route::get('/internship-companies', [InternshipController::class, 'companies'])->name('api.internship-companies.index');
    Route::post('/internship-companies', [InternshipController::class, 'storeCompany'])->name('api.internship-companies.store');
    Route::match(['put', 'patch'], '/internship-companies/{company}', [InternshipController::class, 'updateCompany'])->name('api.internship-companies.update');
    Route::get('/internships', [InternshipController::class, 'index'])->name('api.internships.index');
    Route::post('/internships', [InternshipController::class, 'store'])->name('api.internships.store');
    Route::get('/internships/{internship}', [InternshipController::class, 'show'])->name('api.internships.show');
    Route::match(['put', 'patch'], '/internships/{internship}', [InternshipController::class, 'update'])->name('api.internships.update');
    Route::post('/internships/{internship}/reports', [InternshipController::class, 'report'])->name('api.internships.reports.store');
    Route::post('/internships/{internship}/evaluations', [InternshipController::class, 'evaluate'])->name('api.internships.evaluations.store');
    Route::post('/internships/{internship}/{action}', [InternshipController::class, 'transition'])->whereIn('action', ['submit', 'review', 'approve', 'reject', 'start', 'complete', 'cancel'])->name('api.internships.transition');
    Route::post('/internship-reports/{report}/review', [InternshipController::class, 'reviewReport'])->name('api.internship-reports.review');
    Route::get('/internship-reports/{report}/file', [InternshipController::class, 'downloadReport'])->name('api.internship-reports.file');
});

// A student may read their own profile; `StudentPolicy::view` limits them to it.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,department-admin,student'])->group(function () {
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('api.students.show');
    Route::get('/students/{student}/dashboard', StudentDashboardController::class)->name('api.students.dashboard');
});

// A lecturer may read their own profile; `LecturerPolicy::view` limits them to it.
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,department-admin,lecturer'])->group(function () {
    Route::get('/lecturers/{lecturer}', [LecturerController::class, 'show'])->name('api.lecturers.show');
    Route::get('/lecturers/{lecturer}/sections', [LecturerController::class, 'sections'])->name('api.lecturers.sections');
    Route::get('/lecturers/{lecturer}/dashboard', LecturerDashboardController::class)->name('api.lecturers.dashboard');
    Route::get('/timetable/lecturer/{lecturer}', [ScheduleController::class, 'lecturer'])->name('api.timetable.lecturer');
});

// Offerings, sections, lecturer assignments and weekly class times: managers
// for every offering, a Department Admin for their department's courses
// (`CourseOfferingPolicy`, report 46).
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,department-admin'])->group(function () {
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
});

Route::middleware(['auth:sanctum', 'role:super-admin,university-admin'])->group(function () {
    Route::get('/university/dashboard', UniversityDashboardController::class)->name('api.university.dashboard');
    Route::post('/universities', [UniversityController::class, 'store'])->name('api.universities.store');
    Route::match(['put', 'patch'], '/universities/{university}', [UniversityController::class, 'update'])
        ->name('api.universities.update');
    Route::post('/universities/{university}/current', [UniversityController::class, 'makeCurrent'])
        ->name('api.universities.current');
    Route::delete('/universities/{university}', [UniversityController::class, 'destroy'])
        ->name('api.universities.destroy');

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

    // Audit trail (module 9.24): read-only; rows are written by `AuditLogger`.
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('api.audit-logs.index');
    Route::get('/audit-logs/export', [ExportController::class, 'auditLogs'])->name('api.audit-logs.export');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('api.audit-logs.show');
});

// Analytics (module 9.23): managers for the university or one department, a
// Department Admin for their own department (`view-analytics`, report 47).
Route::middleware(['auth:sanctum', 'role:super-admin,university-admin,department-admin'])->prefix('/analytics')->name('api.analytics.')->group(function () {
    Route::get('/overview', [AnalyticsController::class, 'overview'])->name('overview');
    Route::get('/enrollment', [AnalyticsController::class, 'enrollment'])->name('enrollment');
    Route::get('/academic', [AnalyticsController::class, 'academic'])->name('academic');
    Route::get('/administrative', [AnalyticsController::class, 'administrative'])->name('administrative');
    Route::get('/trends', [AnalyticsController::class, 'trends'])->name('trends');
    Route::get('/export', [ExportController::class, 'analytics'])->name('export');
    Route::get('/export/pdf', [ExportController::class, 'analyticsPdf'])->name('export.pdf');
});

// Notification preferences (modules 9.20 / 9.21): always the caller's own.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notification-preferences', [NotificationPreferenceController::class, 'show'])->name('api.notification-preferences.show');
    Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update'])->name('api.notification-preferences.update');
    Route::post('/notification-preferences/test', [NotificationPreferenceController::class, 'test'])
        ->middleware('throttle:notification-test')
        ->name('api.notification-preferences.test');
});

// In-app inbox (report 42): always the caller's own notifications.
Route::middleware('auth:sanctum')->prefix('/notifications')->name('api.notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/read-all', [NotificationController::class, 'readAll'])->name('read-all');
    Route::post('/{notification}/read', [NotificationController::class, 'read'])->whereUuid('notification')->name('read');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Profile self-service (every signed-in role).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('api.profile.show');
    Route::match(['put', 'post'], '/profile', [ProfileController::class, 'update'])->name('api.profile.update');
});

Route::get('/users/{user}/avatar', [ProfileController::class, 'avatar'])->name('api.avatar.show');

// Public document verification (module 9.17): minimal data, logged, rate limited.
Route::get('/verifications/{token}', [DocumentController::class, 'verify'])
    ->middleware('throttle:verification')
    ->name('api.verifications.show');

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
