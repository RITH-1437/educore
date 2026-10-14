# EduCore — Error & Exception Handling Flows

This document details how HTTP exceptions, domain business rule violations, validation errors, and unexpected server failures are handled, logged, and presented to users.

- **Status:** `[Implemented]`
- **Source Code Reference:** `ErrorLogController.php`, `backend/bootstrap/app.php`, `BusinessRuleException.php`, `backend/routes/web.php`
- **PlantUML Equivalent:** [`puml/error-and-exception-flows.puml`](puml/error-and-exception-flows.puml)

---

## 1. Exception Routing & Recovery Pipeline

```mermaid
flowchart TD
    Request([Incoming Request]) --> Execution[Controller / Service Execution]
    Execution --> ExceptionCaught{Exception Thrown?}

    ExceptionCaught -- No Exception --> NormalResponse[Return 200/201 OK Response]

    ExceptionCaught -- ValidationException --> Handle422[Map to HTTP 422 Unprocessable Entity:<br/>Return JSON / Inertia form error object]
    Handle422 --> DisplayFormErrors[Highlight invalid inputs with inline field messages]

    ExceptionCaught -- AuthenticationException --> Handle401[Map to HTTP 401 Unauthorized:<br/>Redirect to /login with session expired toast]

    ExceptionCaught -- AuthorizationException --> Handle403[Map to HTTP 403 Forbidden:<br/>Render 403 Page & Log Security Audit Event]

    ExceptionCaught -- ModelNotFoundException --> Handle404[Map to HTTP 404 Not Found:<br/>Render friendly 404 Page]

    ExceptionCaught -- BusinessRuleException --> Handle409[Map to HTTP 409 Conflict:<br/>Display user-facing business rule violation banner]

    ExceptionCaught -- ThrottleRequestsException --> Handle429[Map to HTTP 429 Too Many Requests:<br/>Render retry countdown indicator]

    ExceptionCaught -- Unhandled Server Error --> Handle500[Catch in Global Handler:<br/>1. Log stack trace to error_logs table<br/>2. Return clean HTTP 500 error page<br/>3. Redact sensitive credentials/keys]
```

---

## 2. Super Admin System Error Diagnostics

When an unexpected exception occurs, it is persisted to `error_logs` (retained for 90 days per Report 50):

```mermaid
flowchart TD
    ErrorHappens([HTTP 500 or Uncaught Exception]) --> PersistError[Insert row into error_logs:<br/>message, exception_class, file, line,<br/>stack_trace, url, method, user_id, ip_address]
    PersistError --> SuperAdminNotice[Super Admin views /error-logs]
    SuperAdminNotice --> FilterLogs[Filter by Severity / Exception Class]
    FilterLogs --> ViewDetail[Click row -> Inspect full interactive stack trace & environment]
    ViewDetail --> Resolve[Engineer resolves underlying defect]
```
