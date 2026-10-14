# Remediation Log (2026-10-14)

Part of the [post-login audit](post-login-audit.md). Each fix was written after a regression test that
failed on the previous code (FIN-01: simulated), then the full suite was run
([final report](final-audit-report.md) §4).

| Finding | Root cause | Files changed | Fix | Tests | Verification | Remaining risk |
|---|---|---|---|---|---|---|
| BL-01 (P1) | `drop()` predates the grading workflow (report 15 → 20): it looked at `isOpen()` only, and a withdrawn enrollment is excluded from `GRADED_STATUSES`, so a submitted grade became unreachable | `backend/app/Services/EnrollmentService.php` | The grade row is read under `lockForUpdate`; a grade past `draft` refuses the drop (`BusinessRuleException` → 409 on the API, flash error on the web) with a message pointing to "return to draft" | `EnrollmentTest::test_an_enrollment_whose_grade_is_awaiting_approval_cannot_be_dropped` | Failed before (200), passes after; `EnrollmentTest` and `GradingTest` green | Drop window itself undecided (BD-01) |
| SEC-01 (P2) | Sanctum default left unchanged since setup | `backend/config/sanctum.php`, `backend/routes/console.php`, `docker/.env.docker.example`, `backend/.env.example`, `README.md` | `expiration = (int) env('SANCTUM_EXPIRATION', 10080) ?: null`; daily `sanctum:prune-expired --hours=24` at 01:20 | `LoginTest::test_api_tokens_expire_after_the_configured_lifetime` | Failed before (`null`), passes after; `schedule:list` shows the prune job | Existing tokens older than 7 days stop working at deploy — API clients sign in again |
| SEC-02 (P3) | Token deletion fires no auth event; only `login()` dispatched one | `backend/app/Services/AuthService.php` | `logout()` dispatches `Logout('sanctum', $user)`; the existing listener audits it | `LoginTest::test_api_logout_revokes_the_token_and_is_audited` | Failed before (no row), passes after; `AuditTest` green | — |
| FIN-01 (P2) | Check-then-create without a transaction or lock | `backend/app/Services/TuitionInvoiceService.php` | Per-student `DB::transaction`, `Student … lockForUpdate()`, repeated check, skip reason, totals only for created invoices, shared `activeTuitionInvoice()` | `TuitionInvoiceTest::test_a_tuition_invoice_created_meanwhile_is_not_billed_twice` | Passes; all 17 finance tests green | No database constraint yet (follow-up index) |
| UX-01 (P3) | Lecturer profile copy written before report 39 | `frontend/src/pages/Account/Profile.vue` | "Appointment & Position Details", "Employment Status", "Active", fallback "Lecturer" | build | `npm run build` passes | — |
| DOC-01 (P3) | `docs/user-flows/` written without checking the code | `docs/user-flows/*.md` | Core docs rewritten from verified behavior; module docs corrected; each marks what was validated; PlantUML archive marked not revalidated | review | Read against the code (this audit) | `docs/user-flows/puml/*.puml` still mirror the earlier text |

Not changed on purpose: the business decisions BD-01 … BD-06 ([final report](final-audit-report.md) §6),
SEC-04 (validation before policy) and BL-05 (unused states).
