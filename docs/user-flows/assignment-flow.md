# EduCore — Assignment & Coursework Management Flow

This document details how assignments are created by lecturers, how students submit files, how late policies are handled, and how feedback and marks are issued.

- **Status:** `[Implemented]`
- **Source Code Reference:** `CourseworkController.php`, `backend/app/Models/Assignment.php`, `backend/app/Models/AssignmentSubmission.php`, `backend/routes/web.php`
- **PlantUML Equivalent:** [`puml/assignment-flow.puml`](puml/assignment-flow.puml)

---

## 1. End-to-End Assignment & Submission Lifecycle

```mermaid
flowchart TD
    subgraph LecturerSide["Lecturer Workflow"]
        CreateAssign[Lecturer creates Assignment:<br/>Title, Description, Due Date, Points, Attachment] --> PostAssign[POST /coursework/sections/{id}]
        PostAssign --> SaveDB[Insert into assignments table]
        SaveDB --> NotifyStudents[Queue Notification to Enrolled Students]
    end

    subgraph StudentSide["Student Workflow"]
        NotifyStudents --> ViewAssign[Student views Assignment on /my-assignments]
        ViewAssign --> PrepareWork[Student prepares solution file]
        PrepareWork --> UploadFile[Upload File to POST /assignments/{id}/submissions]
        UploadFile --> CheckDeadline{Current Time > Due Date?}
        CheckDeadline -- Past Due & No Late Policy --> BlockSub[Abort 422: Submission closed]
        CheckDeadline -- Past Due & Late Allowed --> SetLate[Set is_late = true]
        CheckDeadline -- On Time --> SetOnTime[Set is_late = false]
        SetLate --> StoreMinIO[Store file in MinIO & Save record in DB]
        SetOnTime --> StoreMinIO
        StoreMinIO --> SubComplete[Submission Recorded]
    end

    subgraph Grading["Review & Feedback Workflow"]
        SubComplete --> ReviewWork[Lecturer downloads submissions & evaluates work]
        ReviewWork --> EnterGrade[Enter Score & Written Feedback]
        EnterGrade --> SaveMarks[POST /submissions/{id}/grade]
        SaveMarks --> StudentNotified[Student receives Grade & Feedback in Portal]
    end
```
