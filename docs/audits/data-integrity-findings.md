# Data Integrity Findings (2026-10-14)

Part of the [post-login audit](post-login-audit.md). Invariants per workflow are listed in
[business-logic-findings](business-logic-findings.md) §3. No schema change was made by this audit.

## 1. Findings

### FIN-01 — Tuition run could bill a student twice (P2, Fixed)

| | |
|---|---|
| Where | `TuitionInvoiceService::generate()` (`POST /api/invoices/generate-tuition`, `POST /invoices/generate-tuition`, `php artisan tuition:generate`) |
| Before | For each student the run asked "is there a non-cancelled invoice for this semester?" and then created one — no transaction, no lock. Two runs at the same time (a double submit, two admins, the command and the UI) could both see "none" and issue two tuition invoices. |
| After | Creation runs in a per-student transaction that first locks the student's row (`lockForUpdate`) and repeats the check; a run that loses the race skips the student with "A tuition invoice for this semester was created meanwhile." Totals count only invoices actually created. One helper (`activeTuitionInvoice`) defines "already billed" for both checks. |
| Test | `TuitionInvoiceTest::test_a_tuition_invoice_created_meanwhile_is_not_billed_twice` inserts the competing run's invoice at the moment the lock is taken and asserts one invoice per student. A real two-connection race cannot run in the single-connection test runner; the lock itself was verified by reading the SQL (`select … for update`). |
| Follow-up | A partial unique index (`invoices (student_id, semester_id) WHERE semester_id IS NOT NULL AND status <> 'cancelled' AND deleted_at IS NULL`) would make the database enforce it too. Only tuition invoices carry `semester_id` today, but adding the index is a schema change and was left for a planned migration. |

### BL-01 — Orphaned submitted grade after withdrawal (P1, Fixed)

Data side of BL-01: a `submitted` grade could remain on a `withdrawn` enrollment that no screen or
workflow could reach again. Prevented at the source (`EnrollmentService::drop`, grade row locked).
Existing data: a query for `grades.status <> 'draft'` joined to `enrollments.status IN ('dropped','withdrawn')`
finds any such row; the development database has none.

### BL-04 — Completed enrollment with a draft grade (P3, Documented)

See [business-logic-findings](business-logic-findings.md) BL-04.

## 2. Verified integrity mechanisms

| Concern | Mechanism | Where |
|---|---|---|
| Uniqueness | `uq_enrollments_student_section`, `uq_grades_enrollment`, `uq_exam_results`, `uq_assignment_submissions`, `uq_attendance_records_session_enrollment`, `uq_attendance_sessions_section_date`, `uq_section_lecturers`, `uq_section_lecturers_primary` (partial), `uq_student_programs_active` (partial), `uq_internships_active` (partial), `uq_invoices_number`, `uq_course_offerings`, `uq_sections_offering_code`, `uq_schedule_room_slot`, `uq_gpa_records` | migrations |
| Concurrency | Row locks: offering + section on enrollment; grade rows on submit / approve / return / finalize / reopen and (new) on drop; invoice on payment, reversal, edit, cancel; payment on reversal; document request on every transition; announcement on publish; active Super Admins on demotion; student on tuition creation (new) | services |
| Transactions | Every multi-step write (enrollment, grading steps, document generation, invoices and payments, tuition per student, internship transitions, announcement publish, profile with upload) runs in `DB::transaction`; uploads are written first and removed if the transaction fails | services |
| Money | `decimal(12,2)` columns; arithmetic in integer cents (`Invoice::cents`, `balanceCents`); payments capped at the balance | `InvoiceService` |
| Grades / GPA | `total_score decimal(6,2)`; GPA recomputed from approved / finalized grades, credit-weighted, on every approval or return; retakes replace earlier attempts | `GpaService` |
| Deletion | Academic, financial and audit records are not cascaded away: soft deletes, archive-vs-delete with guards that count soft-deleted children, cancellation instead of deletion for invoices, append-only payments and audit log; cascades (9 foreign keys) only for pivot and child rows: `permission_role`, `course_programs`, `course_prerequisites`, `section_lecturers`, `invoice_items` (invoices are cancelled, never deleted, once issued) and `announcement_reads` | migrations, reports 7, 23, 28 |
| Time | Academic dates on the university clock (`AcademicClock`, `ACADEMIC_TIMEZONE`); finance dates on the application day (report 50 decision) | services |
| Orphans | Foreign keys on every relationship column; profile accounts cannot change role or be deleted while holding a student / lecturer profile (report 50) | migrations, `UserService` |

## 3. Residual risks

- Two lecturers recording the **same** session date at the same instant: `firstOrNew` + save can hit
  `uq_attendance_sessions_section_date` and answer 500 for the second request (no data corruption).
- `uq_internships_active` covers submitted → in progress; a second **draft** application is prevented
  in the service only.
- No database-level guard for one active tuition invoice per semester (FIN-01 follow-up).
