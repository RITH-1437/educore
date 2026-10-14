# EduCore — Analytics & Reporting Flow

This document details the analytics data aggregation pipelines, departmental performance metrics, semester trend comparisons, and CSV/PDF export workflows.

- **Status:** `[Implemented]`
- **Source Code Reference:** `AnalyticsPageController.php`, `ExportController.php`, `AnalyticsService.php`, `backend/routes/web.php`
- **PlantUML Equivalent:** [`puml/analytics-and-reporting-flow.puml`](puml/analytics-and-reporting-flow.puml)

---

## 1. Analytics Aggregation & Scoped Reporting Architecture

```mermaid
flowchart TD
    Request([User navigates to /analytics]) --> RoleScope{User Role?}

    RoleScope -- Super Admin --> GlobalMetrics[Query Global Institutional Metrics:<br/>Total Universities, Users across all roles,<br/>Platform Storage Consumption, Global Retention]
    
    RoleScope -- University Admin --> UniMetrics[Query University-Wide Metrics:<br/>Active Enrollments by Faculty/Dept, Total Invoiced vs Collected,<br/>Student Pass/Fail Distribution, Semester Retention Trends]
    
    RoleScope -- Department Admin --> DeptMetrics["Query Scoped Department Metrics:<br/>Department Major Enrolment Breakdown, Average Course GPA,<br/>Lecturer Teaching Load, At-Risk Student Count (GPA below 2.0)"]

    GlobalMetrics --> RenderAnalytics[Render Interactive Vue Analytics Dashboards:<br/>Pie/Donut Charts, Bar Graphs, Key Metric Cards]
    UniMetrics --> RenderAnalytics
    DeptMetrics --> RenderAnalytics

    RenderAnalytics --> ExportDecision{Export Data?}
    ExportDecision -- CSV Export --> StreamCSV[Invoke GET /analytics/export<br/>Stream real-time CSV response]
    ExportDecision -- PDF Summary --> GeneratePDF[Invoke GET /analytics/export/pdf<br/>Render clean executive report via DomPDF]
    StreamCSV --> DownloadDone([File Downloaded])
    GeneratePDF --> DownloadDone
```

---

## 2. Department Analytics Comparison & Semester Trends

```mermaid
flowchart LR
    EnrollmentStats[Enrollment Data] --> TrendAggregator[Analytics Service Aggregator]
    GradeStats[Final Grades & GPA] --> TrendAggregator
    InvoiceStats[Tuition Collection] --> TrendAggregator
    AttendanceStats[Attendance Sessions] --> TrendAggregator

    TrendAggregator --> Chart1[Department Enrollment Comparison]
    TrendAggregator --> Chart2[GPA Distribution Curve]
    TrendAggregator --> Chart3[Tuition Collection vs Outstanding Aging]
    TrendAggregator --> Chart4[Semester-over-Semester Retention Rate]
```
