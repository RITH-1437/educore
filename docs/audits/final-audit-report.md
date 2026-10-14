# Post-Login Workflow, Business Logic, RBAC & System Integrity Audit Report

- **Date:** 2026-10-14
- **Lead Auditors:** Principal Software Architect, Senior Laravel Engineer, Senior Vue.js Engineer, Database Architect, Security & QA Automation Engineer
- **Scope:** Complete post-login journey for all 5 authorized roles across all 450 application routes, 25 policies, Eloquent models, services, transactions, and UI workflows up to sign-out.
- **Reference Deliverables:**
  - [Post-Login Baseline & Register](post-login-audit.md)
  - [Role × Permission Matrix](role-permission-matrix.md)
  - [Route & API Endpoint Permission Matrix](route-api-permission-matrix.md)
  - [Business Logic Findings & State Machines](business-logic-findings.md)
  - [Security Findings & Isolation Probes](security-findings.md)
  - [Data Integrity & Invariant Review](data-integrity-findings.md)
  - [Regression Test Matrix](regression-test-matrix.md)
  - [Remediation Log](remediation-log.md)

---

## 1. Executive Summary

A comprehensive post-login audit was conducted across the entire **EduCore** application. The investigation evaluated every layer of the system: frontend routing and UI contracts, Laravel middleware and policies, service-layer state machines, database transactions and concurrency row locks, and background notification dispatches.

### Key Audit Findings & Remediations
1. **Critical Privilege & BOLA Assessment:**
   - **Zero P0 vulnerabilities found.** No authentication bypass, privilege escalation, cross-tenant/cross-department data leakage, or destructive data loss was identified.
   - Dynamic isolation probes (`RoleIsolationMatrixTest`) verified 75 distinct cross-department, cross-lecturer, and cross-student boundary checks; all unauthorized accesses are strictly rejected with `HTTP 403 Forbidden`.
2. **Business Logic Flaw in Course Drops (BL-01 — P1 Fixed):**
   - *Problem:* An enrolled student could drop a course after the lecturer had already calculated and submitted final grades for department review. Dropping moved the enrollment to `withdrawn`, which removed it from the grade approval sheet, leaving an orphaned submitted grade and allowing students to dodge submitted failing grades.
   - *Fix:* Enforced a strict lock check in `EnrollmentService::drop()` refusing withdrawal with `HTTP 409 Conflict` if the associated grade has progressed past `draft`.
3. **API Token Lifetime Hardening (SEC-01 — P2 Fixed):**
   - *Problem:* `sanctum.expiration` defaulted to `null`, meaning issued API tokens never expired.
   - *Fix:* Configured `SANCTUM_EXPIRATION` (defaulting to 7 days / 10,080 minutes) and registered a daily background scheduled prune job (`sanctum:prune-expired --hours=24` at 01:20).
4. **API Sign-Out Audit Trail (SEC-02 — P3 Fixed):**
   - *Problem:* API token deletion omitted the `auth.logout` audit log event that web sessions fired.
   - *Fix:* Dispatched `Logout('sanctum', $user)` on API sign-out.
5. **Tuition Invoicing Race Condition (FIN-01 — P2 Fixed):**
   - *Problem:* Batch tuition invoice generation performed a check-then-insert without a row lock, risking duplicate billing on concurrent runs.
   - *Fix:* Added per-student transactions with `lockForUpdate` and re-checked existing active invoices inside the lock.
6. **Documentation Synchronization (DOC-01 — P3 Fixed):**
   - *Problem:* Earlier draft flowcharts contained speculative constructs (e.g. Finance Officer role, online card gateways, Spatie role table conventions).
   - *Fix:* Fully synchronized `docs/user-flows/` with actual codebase realities.

---

## 2. Testing & Verification Baseline

| Check | Tool / Framework | Target | Baseline | Post-Audit Status |
|---|---|---|---|---|
| **PHP Feature & Unit Tests** | PHPUnit / Pest / Artisan | PostgreSQL (`educore_test`) | 529 passed | **537 passed (5,468 assertions)** |
| **Code Style** | Laravel Pint | `backend/` | Clean | **Clean** |
| **Frontend Production Build** | Vite + Rolldown | `frontend/src` | Clean | **Clean (0 errors, 8.38s)** |
| **API Contract & Permissions** | Dynamic Probes | `RoleIsolationMatrixTest` | N/A | **100% Pass (75 own/other probes)** |

---

## 3. Findings & Resolution Summary

| ID | Sev | Category | Title | Status | Verification Evidence |
|---|---|---|---|---|---|
| **BL-01** | P1 | Business Logic | Course drop permitted on submitted final grades | **Fixed** | `EnrollmentTest::test_an_enrollment_whose_grade_is_awaiting_approval_cannot_be_dropped` |
| **SEC-01** | P2 | Security | Indefinite API token lifetime | **Fixed** | `LoginTest::test_api_tokens_expire_after_the_configured_lifetime` |
| **FIN-01** | P2 | Concurrency | Concurrent tuition generation race condition | **Fixed** | `TuitionInvoiceTest::test_a_tuition_invoice_created_meanwhile_is_not_billed_twice` |
| **SEC-02** | P3 | Audit Logging | Missing audit trail on API token sign-out | **Fixed** | `LoginTest::test_api_logout_revokes_the_token_and_is_audited` |
| **UX-01** | P3 | User Experience | Stale "Faculty" terminology on Lecturer Profile | **Fixed** | Verified in `frontend/src/pages/Account/Profile.vue` |
| **DOC-01**| P3 | Documentation | Discrepancies between flowcharts and implementation | **Fixed** | `docs/user-flows/*.md` updated and validated |
| **BL-02** | P2 | Business Rule | Unbounded course withdrawal window | **Documented (BD-01)** | Documented for institutional policy decision |
| **BL-03** | P2 | Business Rule | Grading allowed in completed semester | **Documented (BD-02)** | Documented for institutional policy decision |
| **BL-04** | P3 | Business Rule | Return-to-draft preserves 'completed' enrollment status | **Documented (BD-04)** | Documented for institutional policy decision |
| **SCOPE-01**| P2 | Scoping | Transferred student visible to former department | **Documented (BD-05)** | Documented for institutional policy decision |
| **SEC-03** | P3 | Privacy | Public sequential user avatar URLs | **Documented (BD-06)** | Accepted architectural design decision |
| **SEC-04** | P3 | Validation | Form request 422 before policy 403 on malformed inputs | **Accepted** | Standard Laravel lifecycle; leaks no private data |

---

## 4. Business Decisions Requiring Product Owner Resolution

The following business rules require institutional or product-owner sign-off before implementation:

1. **BD-01 (Course Drop Deadline):** Determine whether course drops are restricted strictly to the open registration window or whether an explicit "Late Withdrawal" period (e.g., Week 10) with a transcript mark (`W`) should be formalized.
2. **BD-02 (Grading in Completed Semesters):** Determine whether grade calculation and submission by lecturers should be hard-frozen when a semester transitions to `completed`, reserving reopen capabilities exclusively to Super Admin audited workflows.
3. **BD-03 (Multi-Institution Tenancy):** Currently, the platform runs a single `is_current` institution. If true multi-university tenancy is planned, explicit university bindings on `User` models and university-scoping middleware must be added.
4. **BD-04 (Enrollment Status on Grade Rollback):** Determine whether returning an approved grade to draft should downgrade the enrollment status from `completed` back to `confirmed`.
5. **BD-05 (Department Admin History after Student Transfer):** Decide whether a department administrator should retain read access to a transferred student's past records from when they were in that department.
6. **BD-06 (User Avatar Privacy):** Decide whether user avatars should remain public assets or require authenticated signed URLs.

---

## 5. Conclusion & Production Readiness

The post-login architecture of EduCore is solid, strictly scoped, and demonstrates robust defense-in-depth principles:
- Server-side authorization policies reliably back every UI action.
- Destructive cascades are strictly prevented in favor of soft-deletes and audited state transitions.
- All 537 feature and unit tests pass with zero failures.
- Production assets compile cleanly without warnings or errors.
