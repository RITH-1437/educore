# EduCore — Lecturer User Flow

This document details the workflows, operations, scope, and decision paths for the **Lecturer** role in EduCore.

- **Status:** `[Implemented]`
- **Primary Routes:** `/dashboard`, `/timetable`, `/attendance`, `/attendance/sections/{id}`, `/coursework/sections/{id}`, `/exams/sections/{id}`, `/grades/sections/{id}`, `/catalog`, `/grading-scale`, `/announcements`
- **PlantUML Equivalent:** [`puml/lecturer-flow.puml`](puml/lecturer-flow.puml)

---

## 1. Role Overview

### Role Purpose & Responsibilities
Lecturers manage the active instruction, evaluation, and classroom administration of their assigned course sections. They view their personal teaching timetable, initiate and record daily attendance sessions, post coursework assignments and learning materials, create exam assessments, enter marks, compute final letter grades, and submit grades for administrative approval.

### Allowed Operations
- Access lecturer workspace dashboard (`/dashboard`) showing active sections and upcoming classes.
- View personal weekly teaching timetable (`/timetable`).
- Start and manage attendance sessions for assigned sections (`/attendance/sections/{id}`).
- Upload and distribute course syllabus and learning materials (`/coursework/sections/{id}/materials`).
- Create coursework assignments, download student submissions, and record marks (`/coursework/sections/{id}`).
- Create section examinations and record exam results (`/exams/sections/{id}`).
- Enter grade components, trigger automated final grade computations, and submit grades for approval (`/grades/sections/{id}`).
- Post announcements targeted to students enrolled in their assigned sections (`/announcements`).
- View institutional course catalog and official grading scale standards (`/catalog`, `/grading-scale`).

### Restricted Operations
- Cannot access or record attendance, coursework, exams, or grades for sections they are not assigned to teach.
- Cannot finalize (lock) final grades or alter finalized transcripts (reserved for Department/University Admins).
- Cannot modify course prerequisites, department curricula, or university billing.

---

## 2. Lecturer Navigation & Workspace Dashboard

```mermaid
flowchart TD
    Login([Lecturer Logs In]) --> AuthCheck{Credentials & 2FA Valid?}
    AuthCheck -- No --> ShowErr[Display Login Error]
    AuthCheck -- Yes --> Redir[/dashboard]

    Redir --> LoadWorkspace[Load Lecturer Workspace:<br/>Assigned Sections for Current Term,<br/>Today's Classes & Timetable, Recent Submissions]
    LoadWorkspace --> RenderDashboard[Render Lecturer Dashboard]

    RenderDashboard --> MenuChoice{Select Sidebar Workspace Item}
    
    MenuChoice --> NavDash[Dashboard: /dashboard]
    MenuChoice --> NavTime[My Timetable: /timetable]
    MenuChoice --> NavAtt[Attendance: /attendance]
    MenuChoice --> NavCat[Course Catalog: /catalog]
    MenuChoice --> NavScale[Grading Scale: /grading-scale]
    MenuChoice --> NavAnn[Announcements: /announcements]
```

---

## 3. Attendance Session Initiation & Recording Flow

Lecturers record class attendance for each scheduled session.

```mermaid
flowchart TD
    StartAtt([Lecturer opens /attendance]) --> SelectSection[Select Assigned Class Section]
    SelectSection --> OpenSheet[Navigate to /attendance/sections/{id}]
    OpenSheet --> CheckExisting{Open Attendance Session exists for today?}

    CheckExisting -- No --> StartNewSession[Click 'Start Attendance Session'<br/>Specify Date & Session Type]
    StartNewSession --> CreateSessionDB[Insert attendance_sessions record]
    CreateSessionDB --> DisplayRoster[Display Enrolled Students Roster]

    CheckExisting -- Yes --> DisplayRoster

    DisplayRoster --> MarkStudents[For each student, select status:<br/>Present / Absent / Late / Excused<br/>Optional Remarks]
    MarkStudents --> SubmitAtt[Submit POST /attendance/sections/{id}]
    SubmitAtt --> ValidateAtt{Check all active students accounted for?}
    ValidateAtt -- Missing records --> PromptComplete[Highlight unrecorded students]
    PromptComplete --> MarkStudents
    ValidateAtt -- Complete --> PersistAtt[Upsert attendance_records in DB]
    PersistAtt --> ToastAttSuccess[Attendance Recorded Successfully]

    OpenSheet -- Session Erroneous --> CancelSession[Click 'Cancel Session']
    CancelSession --> ConfirmCancel{Confirm Cancellation?}
    ConfirmCancel -- Yes --> SubmitCancel[POST /attendance-sessions/{id}/cancel]
    SubmitCancel --> MarkCancelled[Set attendance_sessions status: 'cancelled']
    MarkCancelled --> ToastCancel[Session Cancelled]
```

---

## 4. Coursework, Assignment & Exam Grading Flow

```mermaid
flowchart TD
    StartCoursework([Lecturer opens Section Workspace]) --> SelectActivity{Choose Activity}

    SelectActivity -- Post Learning Material --> OpenMaterials[Click 'Upload Material']
    OpenMaterials --> FileForm[Select PDF/Zip, Title, Week Number]
    FileForm --> SubmitMaterial[POST /coursework/sections/{id}/materials]
    SubmitMaterial --> UploadMinIO[Store file in MinIO & create course_materials record]
    UploadMinIO --> ToastMat[Material Available to Students]

    SelectActivity -- Create Assignment --> OpenAssign[Click 'New Assignment']
    OpenAssign --> AssignForm[Enter Title, Total Points, Due Date, Instructions, File]
    AssignForm --> SubmitAssign[POST /coursework/sections/{id}]
    SubmitAssign --> SaveAssign[Insert assignments record in DB]
    SaveAssign --> NotifyEnrolled[Notify Students of New Assignment]
    NotifyEnrolled --> WaitSubmissions[Students Submit Work]
    WaitSubmissions --> GradeSubmissions[View Submissions & Enter Scores/Feedback]

    SelectActivity -- Record Final Grades --> OpenGrades[Navigate to /grades/sections/{id}]
    OpenGrades --> EnterMarks[Input Continuous Assessment, Midterm, and Final Marks]
    EnterMarks --> ClickCompute[Click 'Compute Final Grades']
    ClickCompute --> AutoCompute[Calculate Total Score according to course weighting<br/>Lookup Letter Grade & Grade Points against Grading Scale]
    AutoCompute --> ReviewSheet[Review Computed Section Grades]
    ReviewSheet --> SubmitForApproval[Click 'Submit for Approval']
    SubmitForApproval --> PostSubmit[POST /grades/sections/{id}/submit]
    PostSubmit --> StatusSubmitted[Set Section Grade Status: 'submitted'<br/>Lock grades against further lecturer editing]
    StatusSubmitted --> NotifyDeptAdmin[Notify Department Admin for Review]
```
