# EduCore — Department Admin User Flow

This document details the workflows, operations, scope, and decision paths for the **Department Admin** role (formerly Faculty Admin) in EduCore.

- **Status:** `[Implemented]`
- **Primary Routes:** `/dashboard`, `/departments`, `/programs`, `/courses`, `/offerings`, `/offerings/{id}/sections`, `/sections/{id}/schedule`, `/students`, `/lecturers`, `/grades`, `/documents`, `/announcements`, `/internships`
- **PlantUML Equivalent:** [`puml/faculty-department-admin-flow.puml`](puml/faculty-department-admin-flow.puml)

---

## 1. Role Overview

### Role Purpose & Responsibilities
The Department Admin leads academic coordination for a specific academic department (assigned via `users.department_id`). They oversee departmental programs, assign lecturers to courses, configure course offerings and class sections, coordinate weekly timetables and room assignments, review student document requests, approve submitted final grades, and broadcast department-specific announcements.

### Allowed Operations
- Access department-scoped dashboard (`/dashboard`) and departmental trends (`/analytics`).
- Update departmental information for their assigned department (`/departments/{id}/edit`).
- Manage academic programs and course curricula under their department (`/programs`).
- Manage departmental courses and prerequisite rules (`/courses`).
- Create course offerings, class sections, and assign lecturers (`/offerings`).
- Schedule weekly timetable entries and resolve room/lecturer conflicts (`/sections/{id}/schedule`).
- Review and approve/return submitted section grades (`/grades/sections/{id}/approve`, `/grades/sections/{id}/return`).
- Review and approve student document requests and grant fee waivers where eligible (`/documents`).
- Publish announcements targeted to students and lecturers in their department (`/announcements`).
- Review and manage student internship applications within their department (`/internships`).

### Restricted Operations
- **Cross-Department Access:** Cannot view or edit programs, courses, lecturers, students, or grade submissions belonging to another department (`users.department_id != resource.department_id`).
- **Academic Years:** Cannot create, edit, or delete academic years and semesters (`/academic-years` is read-only or hidden).
- **Tuition & Document Types:** Cannot configure document types, fee pricing, or issue university-wide tuition batches (`/document-types`, `/invoices` billing is university-admin only).
- **User Account Administration:** Cannot access `/users` to create or alter system users.

---

## 2. Department Admin Navigation & Dashboard Access

```mermaid
flowchart TD
    Login([Department Admin Logs In]) --> AuthCheck{Credentials & 2FA Valid?}
    AuthCheck -- No --> ShowErr[Display Login Error]
    AuthCheck -- Yes --> Redir[/dashboard]

    Redir --> LoadDeptData[Query Department Context:<br/>users.department_id]
    LoadDeptData --> FetchDeptMetrics[Fetch Metrics for Assigned Department:<br/>Active Programs, Department Students, Assigned Lecturers,<br/>Pending Grade Approvals, Pending Document Requests]
    FetchDeptMetrics --> RenderDashboard[Render Department Admin Dashboard]

    RenderDashboard --> MenuChoice{Select Sidebar Navigation Category}
    
    MenuChoice --> NavOverview[Overview: /dashboard, /analytics]
    MenuChoice --> NavStructure[Academic Structure: /departments, /programs, /courses, /offerings, /enrollments, /rooms]
    MenuChoice --> NavPeople[People: /students, /lecturers]
    MenuChoice --> NavAssessment[Assessment: /grades, /grading-scale]
    MenuChoice --> NavOps[Operations: /announcements, /documents, /internships]
```

---

## 3. Section Creation, Lecturer Assignment & Timetable Scheduling Flow

```mermaid
flowchart TD
    StartOffering([Admin opens /offerings]) --> SelectOffering[Select Course Offering for Current Term]
    SelectOffering --> ClickAddSection[Click 'Add Section']
    ClickAddSection --> SectionForm[Enter Section Code e.g. CS101-A, Capacity e.g. 35]
    SectionForm --> SubmitSection[POST /offerings/{id}/sections]
    SubmitSection --> SaveSection[Save Section in DB]
    
    SaveSection --> AssignLecturer[Click 'Assign Lecturer']
    AssignLecturer --> SelectLecturer[Pick Lecturer belonging to Department]
    SelectLecturer --> SubmitLecturer[POST /sections/{id}/lecturers]
    SubmitLecturer --> LinkLecturer[Insert section_lecturers association]

    LinkLecturer --> AddSchedule[Click 'Add Weekly Schedule']
    AddSchedule --> ScheduleForm[Select Day of Week, Start Time, End Time, Room]
    ScheduleForm --> SubmitSchedule[POST /sections/{id}/schedule]

    SubmitSchedule --> ConflictCheck{Timetable Conflict Check:<br/>1. Room occupied at same day/time?<br/>2. Lecturer teaching another section?<br/>3. Section already scheduled?}
    
    ConflictCheck -- Room Conflict --> RoomErr[Abort 422: Room already booked for that timeslot]
    ConflictCheck -- Lecturer Conflict --> LectErr[Abort 422: Lecturer is scheduled elsewhere]
    ConflictCheck -- Section Conflict --> SecErr[Abort 422: Section has overlapping schedule]

    RoomErr --> ScheduleForm
    LectErr --> ScheduleForm
    SecErr --> ScheduleForm

    ConflictCheck -- No Conflict --> SaveSchedule[Persist schedule_entries in DB]
    SaveSchedule --> ScheduleDone([Timetable Slot Successfully Scheduled])
```

---

## 4. Grade Submission Review & Approval Workflow

```mermaid
flowchart TD
    StartGrades([Admin navigates to /grades]) --> FilterDept[Display sections belonging to user.department_id]
    FilterDept --> SelectSection[Select Section in 'submitted' status]
    SelectSection --> ViewGradeSheet[Open /grades/sections/{id}]
    ViewGradeSheet --> ReviewMarks[Inspect Continuous Assessment, Midterm, Final Marks & Computed Final Grade]

    ReviewMarks --> DecideApproval{Approve or Return Grades?}

    DecideApproval -- Request Revisions --> ClickReturn[Click 'Return for Revision']
    ClickReturn --> EnterRemarks[Provide feedback reason to Lecturer]
    EnterRemarks --> SubmitReturn[POST /grades/sections/{id}/return]
    SubmitReturn --> SetDraft[Update section grade status back to 'draft']
    SetDraft --> NotifyLecturer[Send Notification to Lecturer to revise]
    NotifyLecturer --> EndReturned([Returned to Draft])

    DecideApproval -- Approve Grades --> ClickApprove[Click 'Approve Grades']
    ClickApprove --> SubmitApprove[POST /grades/sections/{id}/approve]
    SubmitApprove --> SetApproved[Update section grade status to 'approved']
    SetApproved --> NotifyDean[Ready for Finalization by University Admin or Self]
    NotifyDean --> EndApproved([Grades Approved])
```
