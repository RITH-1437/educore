# EduCore — Student User Flow

This document details the workflows, operations, scope, and decision paths for the **Student** role in EduCore.

- **Status:** `[Implemented]`
- **Primary Routes:** `/dashboard`, `/registration`, `/catalog`, `/timetable`, `/my-materials`, `/my-assignments`, `/my-exams`, `/my-attendance`, `/my-grades`, `/my-documents`, `/my-invoices`, `/my-internships`, `/announcements`
- **PlantUML Equivalent:** [`puml/student-flow.puml`](puml/student-flow.puml)

---

## 1. Role Overview

### Role Purpose & Responsibilities
Students are the primary academic recipients of the platform. They register for courses during open enrollment windows, view their personalized weekly timetable, access course learning materials, submit assignments, track exam schedules, view attendance percentages, check published grades and GPA, request official university documents (transcripts, certificates), view tuition invoices, and track internship progress.

### Allowed Operations
- Access personal student academic dashboard (`/dashboard`).
- Browse available course offerings and self-register during active registration windows (`/registration`).
- Browse general university course catalog and prerequisites (`/catalog`).
- View personalized weekly timetable and room locations (`/timetable`).
- Download lecture materials posted by assigned lecturers (`/my-materials`).
- View assignment requirements and submit homework files (`/my-assignments`).
- View scheduled midterm and final examinations (`/my-exams`).
- Inspect personal attendance rates and individual session records (`/my-attendance`).
- View published course grades, earned credits, term GPA, and cumulative GPA (`/my-grades`).
- Submit document requests (e.g. Certificate of Study, Transcript) and download issued PDFs (`/my-documents`).
- Verify authenticity of issued documents via public QR verification links (`/verify/{token}`).
- View tuition invoices, remaining balances, and download payment receipts (`/my-invoices`).
- Submit internship application proposals and upload weekly progress reports (`/my-internships`).
- View university and department announcements (`/announcements`).

### Restricted Operations
- **Strict Personal Ownership:** Students can only view their own grades, attendance, invoices, document requests, and submissions (`student_id == auth()->user()->student_id`).
- Cannot edit attendance, publish grades, or create course sections.
- Cannot self-register if registration window is closed, prerequisites are unmet, or timetable conflicts exist.

---

## 2. Student Navigation & Academic Dashboard Access

```mermaid
flowchart TD
    Login([Student Logs In]) --> AuthCheck{Credentials & 2FA Valid?}
    AuthCheck -- No --> ShowErr[Display Login Error]
    AuthCheck -- Yes --> Redir[/dashboard]

    Redir --> LoadStudentData[Load Student Academic Summary:<br/>Active Program & Semester, Enrolled Courses,<br/>Cumulative GPA, Attendance Rate, Outstanding Balance]
    LoadStudentData --> RenderDashboard[Render Student Dashboard]

    RenderDashboard --> MenuChoice{Select Sidebar Navigation Category}
    
    MenuChoice --> NavOverview[Overview: /dashboard, /announcements]
    MenuChoice --> NavLearning[Learning: /registration, /catalog, /timetable, /my-materials, /my-assignments, /my-exams, /my-attendance]
    MenuChoice --> NavRecords[Records: /my-grades, /my-documents, /my-invoices, /my-internships]
```

---

## 3. Course Registration & Prerequisite Validation Flow

```mermaid
flowchart TD
    StartReg([Student opens /registration]) --> CheckWindow{Is Semester Registration Window Open?<br/>semesters.status == 'open'}

    CheckWindow -- No --> WindowClosedErr[Display 'Course registration is currently closed']
    WindowClosedErr --> EndReg([Cannot Register])

    CheckWindow -- Yes --> LoadOfferings[Display Available Course Offerings for Student's Program]
    LoadOfferings --> SelectSection[Student selects desired Section & clicks 'Register']
    SelectSection --> SubmitReg[Submit POST /registration]

    SubmitReg --> PrereqCheck{Prerequisites Satisfied?<br/>Student passed all required courses?}
    PrereqCheck -- Unmet --> PrereqErr[Abort 422: Missing prerequisite course(s)]
    PrereqErr --> LoadOfferings

    PrereqCheck -- Satisfied --> CapacityCheck{"Section Capacity Available?<br/>enrolled_count < max_capacity"}
    CapacityCheck -- Full --> CapErr[Abort 422: Section is full]
    CapErr --> LoadOfferings

    CapacityCheck -- Available --> ConflictCheck{Timetable Conflict?<br/>Overlaps with another registered section?}
    ConflictCheck -- Overlap --> ConflictErr[Abort 422: Time conflict with another enrolled course]
    ConflictErr --> LoadOfferings

    ConflictCheck -- Clear --> CreditCheck{"Credit Limit Exceeded?<br/>current_credits + course_credits <= max_limit"}
    CreditCheck -- Exceeded --> CreditErr[Abort 422: Exceeds maximum semester credit limit]
    CreditErr --> LoadOfferings

    CreditCheck -- Permitted --> InsertEnrollment[Insert record into enrollments table<br/>status: 'enrolled']
    InsertEnrollment --> UpdateCounts[Increment section enrolled_count]
    UpdateCounts --> ToastRegSuccess[Registration Successful Toast]
    ToastRegSuccess --> DoneReg([Student Enrolled in Course])

    StartReg -- Drop Course during window --> SelectEnrolled[Select Enrolled Course]
    SelectEnrolled --> ClickDrop[Click 'Drop Course']
    ClickDrop --> SubmitDrop[POST /registration/{id}/drop]
    SubmitDrop --> UpdateDropStatus[Update enrollment status to 'dropped']
    UpdateDropStatus --> DecrementCount[Decrement section enrolled_count]
    DecrementCount --> ToastDrop[Course Dropped]
```

---

## 4. Assignment Submission Flow

```mermaid
flowchart TD
    StartAssign([Student opens /my-assignments]) --> ViewList[View Pending & Past Assignments]
    ViewList --> SelectAssignment[Select Open Assignment]
    SelectAssignment --> CheckDue{Current Time > Due Date?}

    CheckDue -- Past Due & No Late Submissions --> PastDueErr[Display 'Submission deadline has passed']
    PastDueErr --> EndAssign([Submission Blocked])

    CheckDue -- Within Deadline / Late Allowed --> UploadForm[Choose Homework File (PDF, ZIP, DOCX)]
    UploadForm --> SubmitWork[Submit POST /assignments/{id}/submissions]
    SubmitWork --> ValidateFile{"File size <= 20MB & allowed extension?"}
    ValidateFile -- Invalid --> FileErr[Show 422 File Validation Error]
    FileErr --> UploadForm
    ValidateFile -- Valid --> UploadS3[Upload file to MinIO object storage]
    UploadS3 --> SaveSubmission[Insert / Update assignment_submissions record in DB]
    SaveSubmission --> FlagLate{Submitted after due date?}
    FlagLate -- Yes --> MarkLate[Set is_late = true]
    FlagLate -- No --> MarkOnTime[Set is_late = false]
    MarkLate --> ToastSubmit[Show 'Assignment submitted (Late)' toast]
    MarkOnTime --> ToastSubmit[Show 'Assignment submitted successfully' toast]
```

---

## 5. Document Request & Verification Lifecycle

```mermaid
flowchart TD
    StartDoc([Student opens /my-documents]) --> ClickRequest[Click 'Request Document']
    ClickRequest --> SelectType[Select Document Type e.g. Certificate of Study, Transcript]
    SelectType --> InputDetails[Provide Purpose & Delivery Format (Digital PDF)]
    InputDetails --> SubmitRequest[Submit POST /my-documents]
    
    SubmitRequest --> CheckFee{Document Type has Fee?}
    CheckFee -- Yes --> GenFeeInvoice[Generate Fee Invoice & attach to request<br/>status: 'pending_payment']
    CheckFee -- No --> SetPendingReview[Set status: 'pending_review']
    
    GenFeeInvoice --> PayInvoice[Student views invoice & completes payment offline]
    PayInvoice --> AdminConfirmsPayment[Admin records payment -> status becomes 'pending_review']
    
    SetPendingReview --> AdminReview[Department Admin reviews request]
    AdminReview --> AdminDecision{Approve or Reject?}
    AdminDecision -- Reject --> MarkRejected[Set status: 'rejected' with reason]
    MarkRejected --> NotifyStudentRejected[Notify Student]
    
    AdminDecision -- Approve --> GeneratePDF[System Generates Cryptographic PDF Certificate]
    GeneratePDF --> EmbedQR[Embed Unique QR Code containing /verify/{token}]
    EmbedQR --> StoreMinIO[Save signed PDF to MinIO]
    StoreMinIO --> MarkIssued[Set status: 'issued']
    MarkIssued --> StudentDownload[Student Downloads Official PDF]
    
    StudentDownload --> ThirdPartyVerify[Employer or Authority scans QR Code]
    ThirdPartyVerify --> PublicPortal[Navigates to /verify/{token}]
    PublicPortal --> LookupDB{Token valid & Document active?}
    LookupDB -- Invalid / Revoked --> ShowInvalid[Display 'Invalid or Revoked Document' Warning]
    LookupDB -- Valid --> ShowValid[Display Verified Document Seal, Student Name, Degree, Issue Date]
```
