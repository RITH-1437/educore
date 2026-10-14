# EduCore — Audit Logging & Security Administration Flow

This document details how privileged operations, sensitive record mutations, and security events are tracked across EduCore, and how Super Admins inspect and export audit records.

- **Status:** `[Implemented]`
- **Source Code Reference:** `AuditLogsController.php`, `AuditLogService.php`, `backend/app/Models/AuditLog.php`, `backend/routes/web.php`
- **PlantUML Equivalent:** [`puml/audit-and-security-flow.puml`](puml/audit-and-security-flow.puml)

---

## 1. Audit Log Generation & Event Capture Pipeline

Every sensitive mutation automatically produces an append-only audit log entry:

```mermaid
flowchart TD
    ActionTrigger([Privileged Operation Triggered:<br/>User Created, Grade Finalized, Invoice Paid, 2FA Reset, Auth Failure]) --> ExtractContext[Capture Request Context:<br/>user_id, IP Address, User-Agent, Route, Method]
    ExtractContext --> CaptureDiff[Capture Model State Changes:<br/>old_values JSON vs new_values JSON]
    CaptureDiff --> InsertAuditDB[Insert into audit_logs table:<br/>event, auditable_type, auditable_id,<br/>user_id, ip_address, user_agent, old_values, new_values]
    InsertAuditDB --> Complete[Operation Continues Without Blocking]
```

---

## 2. Super Admin Audit Inspection & Export Workflow

```mermaid
flowchart TD
    Admin([Super Admin opens /audit-logs]) --> ApplyFilter[Filter by: User, Action Type e.g. auth.login, Resource Type, Date Range]
    ApplyFilter --> FetchLogs[Fetch Paginated Records from audit_logs]
    FetchLogs --> RenderTable[Display Audit Log Table]

    RenderTable --> ViewDetails[Click Row -> View JSON Old vs New Payload Comparison]
    RenderTable --> ExportData[Click 'Export CSV' -> GET /audit-logs/export]
    ExportData --> StreamCSV[Download audit_logs_timestamp.csv]
```
