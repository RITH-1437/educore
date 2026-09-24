# EduCore

## University Digital Administration Platform

**Project Type:** Real-World University Information & Administration Platform
**Target Market:** Cambodian Universities
**Architecture:** MVC / Modular Monolith
**Development Team:** Rin Nairith & Lyhor
**Primary Stack:** Laravel + Vue.js
**Database:** PostgreSQL
**Deployment:** Docker + Laravel Cloud
**Project Scope:** MVP → Production-Ready Platform → Advanced Extensions

---

# 1. Executive Summary

UniCore is a centralized digital administration platform designed to modernize university academic and administrative operations in Cambodia.

Many university activities still depend on disconnected systems, spreadsheets, paper documents, messaging applications, and manual administrative processes. This creates difficulties for students, lecturers, departments, and university administrators when managing academic information, schedules, attendance, grades, documents, announcements, and student services.

UniCore aims to provide one centralized platform where students, lecturers, departments, and university administrators can access the information and services relevant to their responsibilities.

The platform will initially focus on academic and administrative workflows rather than attempting to replace every university system.

The first version will provide:

* Student management
* Lecturer management
* Faculty and department management
* Program management
* Course management
* Academic year and semester management
* Class and section management
* Course registration
* Timetable management
* Attendance management
* Assignment management
* Examination management
* Grade management
* GPA calculation
* Announcement management
* Notification management
* Document requests
* Digital invoices and payment records
* Internship management
* Dashboard analytics
* QR-based document verification
* Email and Telegram notifications

The platform will be developed using Laravel and Vue.js with an MVC-oriented backend architecture and a modular monolithic structure.

---

# 2. Project Vision

The vision of UniCore is:

> **To create a modern, secure, centralized digital platform that simplifies university academic and administrative operations while providing students and staff with a consistent digital experience.**

Instead of having information distributed across:

* Paper documents
* Excel files
* Messaging applications
* Separate administrative systems
* Manual approval processes

UniCore brings the major academic workflows into one platform.

### Current Concept

Student → Department → Administration → Lecturer → Separate systems

### UniCore Concept

```text
                    UNICORE
                       │
        ┌──────────────┼──────────────┐
        │              │              │
     Student        Lecturer      Administration
        │              │              │
        └──────────────┼──────────────┘
                       │
              Centralized Platform
                       │
        ┌──────────────┼──────────────┐
        │              │              │
     Academic       Documents      Analytics
     Services        Services       & Reports
```

---

# 3. Problem Statement

Universities manage large amounts of academic and administrative information.

Common problems include:

### 3.1 Information fragmentation

Student information, course information, attendance, grades, and documents may be stored in different systems or files.

### 3.2 Manual processes

Students may need to physically contact departments for:

* Academic documents
* Registration
* Approvals
* Requests
* Information

### 3.3 Limited visibility

University management may have difficulty obtaining real-time information about:

* Enrollment
* Attendance
* Academic performance
* Course performance
* Student activity

### 3.4 Communication problems

Important announcements may be distributed through different communication channels.

### 3.5 Document management

Academic documents can require manual preparation, verification, and distribution.

### 3.6 Scheduling conflicts

Manual timetable creation can result in:

* Teacher conflicts
* Room conflicts
* Class conflicts

### 3.7 Lack of centralized student services

Students may need to use multiple channels to access:

* Courses
* Timetables
* Grades
* Attendance
* Documents
* Announcements
* Internship information

UniCore addresses these problems through a centralized platform.

---

# 4. Project Objectives

## 4.1 General Objective

To develop a centralized digital university administration platform that improves academic management, administrative workflows, communication, and student services.

## 4.2 Specific Objectives

1. Centralize university academic information.
2. Digitize student and lecturer management.
3. Manage faculties, departments, programs, courses, and classes.
4. Provide students with a centralized academic portal.
5. Provide lecturers with tools for attendance, assignments, exams, and grades.
6. Provide administrators with centralized management tools.
7. Digitize document request workflows.
8. Provide invoice and payment-record management.
9. Improve university communication through email and Telegram notifications.
10. Provide academic analytics and reporting.
11. Provide QR-based document verification.
12. Build a scalable architecture that can support future expansion.

---

# 5. Target Users

UniCore will use role-based access control.

## 5.1 Super Administrator

Responsible for the overall platform.

Capabilities include:

* User management
* Role management
* Permission management
* University configuration
* System settings
* Audit logs
* Platform monitoring

---

## 5.2 University Administrator

Responsible for university-level administration.

Capabilities:

* Manage faculties
* Manage departments
* Manage academic programs
* Manage students
* Manage lecturers
* Manage courses
* Manage semesters
* Manage academic years
* Manage announcements
* Review requests
* Manage documents
* Manage payment records
* View reports

---

## 5.3 Faculty / Department Administrator

Responsible for academic operations within their assigned area.

Capabilities:

* Manage students
* Manage lecturers
* Manage courses
* Manage classes
* Manage schedules
* Review student requests
* Monitor attendance
* Monitor academic performance

---

## 5.4 Lecturer

Capabilities:

* View assigned courses
* View enrolled students
* Manage attendance
* Create assignments
* Manage exams
* Submit grades
* Publish course announcements
* Upload course materials

---

## 5.5 Student

Capabilities:

* View profile
* View academic information
* Register for courses
* View timetable
* View attendance
* View assignments
* View examination information
* View grades
* View GPA
* Request documents
* View invoices
* View payment records
* Receive announcements
* Receive notifications
* Manage internship information

---

# 6. Academic Structure

UniCore will support the following hierarchy:

```text
University
    │
    ├── Faculty
    │      │
    │      └── Department
    │              │
    │              └── Program
    │                      │
    │                      └── Courses
    │
    └── Academic Years
            │
            └── Semesters
                    │
                    └── Sections / Classes
```

Example:

```text
University
└── Faculty of Engineering
    └── Department of Computer Science
        └── Bachelor of Computer Science
            ├── Database Systems
            ├── Web Development
            ├── Software Engineering
            └── Computer Networks
```

---

# 7. Major System Modules

## Module 1 — Authentication & Authorization

Authentication will use:

**Student ID + Password**

The system will support role-based authorization.

Core features:

* Login
* Logout
* Password change
* Password reset
* Session management
* Role-based access
* Permission management
* Account activation/deactivation

Laravel Sanctum can be used for API authentication.

---

# 8. Student Management

Administrators can manage:

* Student ID
* Name
* Gender
* Date of birth
* Contact information
* Address
* Profile photo
* Program
* Department
* Faculty
* Academic year
* Enrollment status
* Student status

Possible statuses:

```text
Active
Inactive
Suspended
Graduated
Withdrawn
```

---

# 9. Lecturer Management

The system will manage:

* Lecturer profile
* Employee information
* Department
* Academic position
* Assigned courses
* Teaching schedules
* Contact information
* Status

---

# 10. Faculty & Department Management

Administrators can create and manage:

* Faculties
* Departments
* Programs
* Program structures

Example:

```text
Faculty
  ↓
Department
  ↓
Program
  ↓
Course
```

---

# 11. Course Management

Each course can contain:

* Course code
* Course name
* Description
* Credits
* Department
* Program
* Semester
* Prerequisites
* Lecturer
* Sections
* Schedule

Example:

```text
CS301
Software Engineering
3 Credits

Prerequisites:
CS201
CS202
```

---

# 12. Academic Year & Semester Management

The system will support:

* Academic years
* Semesters
* Enrollment periods
* Registration periods
* Examination periods

Example:

```text
2026–2027
│
├── Semester 1
│
└── Semester 2
```

---

# 13. Class / Section Management

A course can contain multiple sections.

Example:

```text
Database Systems

Section A
Section B
Section C
```

Each section may have:

* Lecturer
* Students
* Room
* Schedule
* Capacity
* Semester

---

# 14. Course Registration

Students can register for available courses.

Workflow:

```text
Student
   ↓
View Available Courses
   ↓
Select Courses
   ↓
Submit Registration
   ↓
Validation
   ↓
Registration Confirmed
```

The system should check:

* Course availability
* Prerequisites
* Duplicate registration
* Semester
* Maximum credits
* Student status

---

# 15. Timetable Management

The timetable system manages:

* Courses
* Lecturers
* Rooms
* Days
* Time slots
* Sections

The system should detect:

### Lecturer conflict

```text
Teacher A
10:00
Course A

Teacher A
10:00
Course B

❌ Conflict
```

### Room conflict

```text
Room 301
10:00
Course A

Room 301
10:00
Course B

❌ Conflict
```

### Section conflict

The same student group should not have two classes at the same time.

---

# 16. Attendance Management

Lecturers can record attendance.

Possible statuses:

```text
Present
Absent
Late
Excused
```

Students can view:

```text
Database Systems
Attendance: 92%

Present: 11
Absent: 1
Late: 0
```

The platform can calculate attendance percentages automatically.

---

# 17. Assignment Management

Lecturers can:

* Create assignments
* Set deadlines
* Upload files
* Add descriptions
* View submissions
* Grade submissions

Students can:

* View assignments
* Download materials
* Submit work
* View results

---

# 18. Examination Management

The system will support:

* Midterm exams
* Final exams
* Quizzes
* Exam schedules
* Exam results

Example:

```text
Database Systems

Midterm       30%
Final         40%
Assignments   20%
Attendance    10%
```

The exact grading configuration should be configurable by the university.

---

# 19. Grade Management

Lecturers submit grades through their course dashboard.

The system calculates:

```text
Score
   ↓
Letter Grade
   ↓
Grade Point
   ↓
Semester GPA
   ↓
Cumulative GPA
```

Example:

```text
A  = 4.0
B+ = 3.5
B  = 3.0
C+ = 2.5
C  = 2.0
D  = 1.0
F  = 0.0
```

The actual grading scale should be configurable.

---

# 20. Student Academic Dashboard

Students should have a centralized dashboard containing:

```text
┌────────────────────────────────────┐
│ Welcome, Student                   │
├────────────────────────────────────┤
│ GPA        Attendance    Credits   │
│ 3.62       94%           96        │
├────────────────────────────────────┤
│ Today's Classes                    │
├────────────────────────────────────┤
│ Upcoming Assignments               │
├────────────────────────────────────┤
│ Announcements                      │
├────────────────────────────────────┤
│ Recent Grades                      │
└────────────────────────────────────┘
```

---

# 21. Document Management

Students can request official documents digitally.

Examples:

* Enrollment certificate
* Student certificate
* Academic transcript
* Academic result
* Internship letter
* Other university documents

Workflow:

```text
Student
   ↓
Document Request
   ↓
Administrator Review
   ↓
Approved
   ↓
Document Generated
   ↓
Student Downloads
```

---

# 22. Digital Document Verification

Generated documents can contain a unique QR code.

Example:

```text
Document
    │
    └── QR Code
          ↓
     Verification URL
          ↓
     UniCore Verification
          ↓
      Valid / Invalid
```

This can help organizations verify whether a document was generated by the university platform.

---

# 23. Invoice & Payment Records

There will be **no dedicated Finance Officer role** in the initial system.

However, administrators can manage financial records.

The system will support:

### Invoices

```text
Student
   ↓
Invoice
   ↓
Amount
   ↓
Due Date
   ↓
Status
```

Statuses:

```text
Pending
Partially Paid
Paid
Overdue
Cancelled
```

### Payment records

The system records:

* Amount
* Payment date
* Payment method
* Reference
* Invoice
* Student

The MVP does **not** require an online payment gateway.

---

# 24. Announcement Management

Administrators and authorized lecturers can publish announcements.

Examples:

* University announcements
* Department announcements
* Course announcements
* Examination announcements
* Registration announcements

Announcements can target:

```text
All Students
Faculty
Department
Program
Class
Course
```

---

# 25. Notification System

The initial notification channels will be:

### Email

Used for:

* Important announcements
* Document status
* Registration confirmation
* Password-related messages
* Administrative notifications

### Telegram

Telegram notifications can be used for:

* Announcements
* Class reminders
* Assignment reminders
* Important academic notifications

The notification architecture should be designed so additional channels can be added later.

---

# 26. Internship Management

UniCore will include a university internship workflow.

```text
Student
   ↓
Internship Application
   ↓
University Review
   ↓
Company Information
   ↓
Approval
   ↓
Internship
   ↓
Reports
   ↓
Supervisor Evaluation
   ↓
Final Evaluation
```

This can later integrate with a larger career platform.

---

# 27. Analytics & Reporting

Administrators will have dashboards showing:

### Student analytics

* Total students
* Active students
* Graduated students
* Withdrawn students

### Academic analytics

* GPA distribution
* Course pass rate
* Attendance
* Course performance

### Enrollment analytics

* Students per program
* Students per department
* Semester enrollment

### Administrative analytics

* Pending requests
* Documents generated
* Outstanding invoices
* Announcements

---

# 28. MVC Architecture

UniCore will use a **Laravel MVC architecture** for the backend.

```text
                    Client
                      │
                      ↓
                 Vue.js Frontend
                      │
                   REST API
                      │
                      ↓
                 Laravel Backend
                      │
          ┌───────────┼───────────┐
          ↓           ↓           ↓
      Controller    Service     Request
          │           │
          ↓           ↓
        Model ───── Repository
          │
          ↓
      PostgreSQL
```

Laravel MVC responsibilities:

### Model

Responsible for:

* Database relationships
* Data representation
* Business entities

### Controller

Responsible for:

* Receiving requests
* Calling application services
* Returning responses

### View

Because the frontend uses Vue, the traditional Laravel Blade view layer will not be the primary UI.

Vue will act as the application interface while Laravel provides the API.

This gives us:

```text
Vue
  ↓
REST API
  ↓
Laravel MVC
  ↓
PostgreSQL
```

---

# 29. Recommended Technology Stack

## Frontend

* Vue 3
* TypeScript
* Tailwind CSS
* Pinia
* Vue Router
* Axios
* Chart.js

## Backend

* Laravel 12
* PHP
* Laravel Sanctum
* REST API
* Laravel Validation
* Laravel Notifications
* Laravel Queues
* Laravel Scheduler

## Database

* PostgreSQL

## Infrastructure

* Docker
* Laravel Cloud
* GitHub

## External services

* Email provider
* Telegram Bot API
* Object/file storage

---

# 30. Frontend Architecture

Recommended Vue structure:

```text
resources/
└── js/
    ├── components/
    ├── layouts/
    ├── pages/
    ├── views/
    ├── stores/
    ├── services/
    ├── composables/
    ├── router/
    ├── types/
    └── utils/
```

The frontend should be organized by feature rather than becoming one huge collection of components.

---

# 31. Laravel Backend Structure

Recommended structure:

```text
app/
├── Models/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
│
├── Services/
├── Repositories/
├── Policies/
├── Notifications/
├── Jobs/
├── Events/
├── Listeners/
└── Enums/
```

Example:

```text
StudentController
      ↓
StudentService
      ↓
StudentRepository
      ↓
Student Model
      ↓
PostgreSQL
```

This keeps controllers thin and business logic organized.

---

# 32. Core Database Entities

The initial database should include entities such as:

```text
users
roles
permissions

students
lecturers

faculties
departments
programs

academic_years
semesters

courses
course_prerequisites
sections
class_enrollments

rooms
schedules

attendance
assignments
assignment_submissions

exams
exam_results
grades

announcements
notifications

document_requests
documents
document_verifications

invoices
payments

internships
internship_reports
internship_evaluations

audit_logs
```

The final ERD should be designed before implementation begins.

---

# 33. Security Requirements

Security is a major requirement because UniCore handles academic records.

The platform should implement:

* Authentication
* Role-based access control
* Permission-based authorization
* Password hashing
* API authentication
* CSRF protection where applicable
* Input validation
* File validation
* Rate limiting
* Audit logs
* Secure document access
* Database constraints
* Access policies
* Secure environment variables

Students must only be able to access their own academic information.

Lecturers must only access courses and students they are authorized to manage.

---

# 34. MVP Definition

MVP means:

> **Minimum Viable Product**

The MVP is not the complete UniCore platform.

Its purpose is to prove that the core university workflow works end-to-end.

## MVP Modules

### Authentication

* Student ID login
* Admin login
* Lecturer login
* Password management
* RBAC

### University Structure

* Faculties
* Departments
* Programs
* Academic years
* Semesters

### People

* Students
* Lecturers

### Academic

* Courses
* Sections
* Enrollment
* Timetable
* Attendance
* Grades
* GPA

### Communication

* Announcements
* Email notifications

### Administration

* Document requests
* Invoice records
* Payment records

### Dashboard

* Student dashboard
* Lecturer dashboard
* Administrator dashboard

---

# 35. What Is NOT in MVP

To control scope, the following should initially be postponed:

* AI assistant
* Mobile application
* Online payment gateway
* Advanced timetable optimization
* Advanced predictive analytics
* Microservices
* Complex integrations
* Advanced chat system
* Automated document OCR
* Large-scale external university integrations

This is important.

The goal is to **finish a reliable core system first**.

---

# 36. Development Phases

## Phase 0 — Research & Planning

Duration: **1 week**

Tasks:

* Requirements analysis
* User stories
* Use cases
* ERD
* Architecture
* UI wireframes
* Git repository setup
* Docker setup
* Development conventions

Deliverables:

* PRD
* ERD
* Architecture diagram
* UI design direction
* Development roadmap

---

# Phase 1 — Foundation

Duration: **1–2 weeks**

Build:

* Laravel project
* Vue project
* PostgreSQL
* Docker
* Authentication
* RBAC
* User management
* Base layouts
* Navigation
* API structure
* Error handling

Deliverable:

> Working authentication and application foundation.

---

# Phase 2 — University Structure

Duration: **1–2 weeks**

Build:

* Faculties
* Departments
* Programs
* Academic years
* Semesters
* Courses
* Rooms

Deliverable:

> Complete university academic structure.

---

# Phase 3 — Student & Lecturer Management

Duration: **1–2 weeks**

Build:

* Student management
* Lecturer management
* Student profiles
* Lecturer profiles
* Program assignment
* Course assignment

Deliverable:

> Students and lecturers are connected to the academic structure.

---

# Phase 4 — Academic Operations

Duration: **2–3 weeks**

Build:

* Course sections
* Enrollment
* Timetable
* Attendance
* Assignments
* Exams
* Grades
* GPA calculation

This is the **core academic engine**.

---

# Phase 5 — Administration

Duration: **1–2 weeks**

Build:

* Document requests
* Document generation
* QR verification
* Invoice records
* Payment records
* Approval workflows

---

# Phase 6 — Communication

Duration: **1 week**

Build:

* Announcements
* Email notifications
* Telegram notifications
* Notification preferences

---

# Phase 7 — Analytics & Dashboard

Duration: **1–2 weeks**

Build:

* Student dashboard
* Lecturer dashboard
* Admin dashboard
* Academic statistics
* Enrollment statistics
* Attendance statistics
* GPA statistics
* Reports

---

# Phase 8 — Testing & Production

Duration: **1–2 weeks**

Tasks:

* Unit testing
* Feature testing
* API testing
* Permission testing
* UI testing
* Security testing
* Database testing
* Performance testing
* Docker production configuration
* Laravel Cloud deployment
* Production monitoring

---

# 37. Overall Development Roadmap

A realistic initial roadmap is approximately:

```text
Week 01
Research + Requirements + Architecture

Week 02
Database + UI + Project Foundation

Week 03
Authentication + RBAC

Week 04
University Structure

Week 05
Students + Lecturers

Week 06
Courses + Sections

Week 07
Enrollment + Timetable

Week 08
Attendance + Assignments

Week 09
Exams + Grades + GPA

Week 10
Documents + Invoices + Payments

Week 11
Notifications + Dashboards

Week 12
Testing + Deployment
```

This gives you a **12-week MVP target**.

After that, continue with V1/V2 rather than delaying the first usable release.

---

# 38. Post-MVP — Version 1

After MVP:

### Academic

* Advanced timetable management
* Course prerequisite engine
* Academic progression tracking
* Graduation eligibility

### Administration

* Advanced document workflows
* More document types
* Digital signatures
* More reporting

### Communication

* Telegram automation
* Notification preferences
* Scheduled announcements

### Analytics

* Advanced dashboards
* Department reports
* Course performance reports
* Student performance trends

---

# 39. Version 2

Possible future features:

* Mobile application
* PWA
* Online payment integration
* Library integration
* Student ID integration
* QR attendance
* More university integrations
* Advanced reporting
* External verification API

---

# 40. Advanced Future Architecture

The MVP should remain a modular monolith.

However, if UniCore becomes a large production platform, modules can eventually be extracted into services.

Initial architecture:

```text
Vue
 ↓
Laravel
 ↓
PostgreSQL
```

Future architecture:

```text
                    API Gateway
                         │
       ┌─────────────────┼─────────────────┐
       ↓                 ↓                 ↓
 Identity           Academic          Administration
 Service             Service             Service
       │                 │                 │
       └─────────────────┼─────────────────┘
                         ↓
                   Event / Queue
                         ↓
             Notification Service
```

Possible future services:

* Identity Service
* Academic Service
* Student Service
* Document Service
* Notification Service
* Payment Service
* Analytics Service

But these should only be introduced when scale actually justifies them.

---

# 41. Development Methodology

The project will use an Agile approach.

Development cycles can be organized into short iterations.

Each feature should follow:

```text
Requirement
    ↓
User Story
    ↓
Database
    ↓
API
    ↓
Frontend
    ↓
Testing
    ↓
Review
    ↓
Merge
```

---

# 42. Git & GitHub Workflow

Recommended branches:

```text
main
develop

feature/authentication
feature/student-management
feature/course-management
feature/attendance
feature/grades
feature/documents
```

Workflow:

```text
Feature Branch
      ↓
Development
      ↓
Pull Request
      ↓
Code Review
      ↓
Merge → develop
      ↓
Testing
      ↓
Release → main
```

Since there are two developers, **Rin + Lyhor**, this workflow will help prevent both of you from stepping on the same code.

---

# 43. Suggested Team Responsibilities

## Rin

Possible focus:

* System architecture
* Laravel backend
* Database
* API
* Authentication
* Core business logic
* DevOps

## Lyhor

Possible focus:

* Vue frontend
* UI/UX implementation
* Dashboard
* Components
* Forms
* Frontend state management

However, both developers should review each other's work rather than completely separating the codebase.

---

# 44. Testing Strategy

Testing should cover:

### Backend

* Model tests
* Service tests
* API feature tests
* Authorization tests

### Frontend

* Component tests
* Form validation
* Navigation
* Permission-based UI

### Integration

Example:

```text
Student Login
      ↓
Course Registration
      ↓
Enrollment
      ↓
Attendance
      ↓
Grade
      ↓
GPA
```

This complete workflow should be tested before MVP release.

---

# 45. Deployment

Development:

```text
Docker
├── Laravel
├── Vue
├── PostgreSQL
└── Redis
```

Production:

```text
GitHub
   ↓
CI/CD
   ↓
Laravel Cloud
   ↓
Production Application
```

Docker should ensure development environments are consistent between Rin and Lyhor.

---

# 46. Expected Outcomes

At the completion of the MVP, UniCore should provide:

1. Centralized student management.
2. Centralized lecturer management.
3. University academic structure management.
4. Course and section management.
5. Student enrollment.
6. Timetable management.
7. Attendance management.
8. Assignment management.
9. Examination management.
10. Grade and GPA management.
11. Digital document requests.
12. QR document verification.
13. Invoice and payment records.
14. Announcements.
15. Email notifications.
16. Telegram notification capability.
17. Academic dashboards.
18. Role-based access control.
19. Secure API architecture.
20. Production deployment.

---

# 47. Success Criteria

The MVP can be considered successful when a complete academic workflow can be performed digitally.

For example:

```text
Admin creates
    ↓
Faculty
    ↓
Department
    ↓
Program
    ↓
Academic Year
    ↓
Semester
    ↓
Course
    ↓
Section
    ↓
Lecturer
    ↓
Student
    ↓
Enrollment
    ↓
Timetable
    ↓
Attendance
    ↓
Assignment
    ↓
Exam
    ↓
Grade
    ↓
GPA
```

And the student can access the result through their own portal.

That end-to-end workflow is the heart of UniCore.

---

# 48. Final Product Positioning

UniCore should not be presented simply as:

> "A Student Management System."

Instead:

> **UniCore is a centralized University Digital Administration Platform designed for Cambodian universities to manage academic operations, student services, administrative workflows, communication, documents, and institutional information through a single secure platform.**

The product focuses on connecting the university's major stakeholders through one digital ecosystem.

---

# 49. Future Vision

The long-term vision is to evolve UniCore into a complete university operating platform.

```text
                    UNICORE
                       │
       ┌───────────────┼────────────────┐
       ↓               ↓                ↓
   Academic       Administration     Student
   Management       Management        Services
       │               │                │
       └───────────────┼────────────────┘
                       ↓
                  Analytics
                       │
                       ↓
              University Intelligence
```

Potential future integrations include:

* Mobile applications
* Digital student IDs
* QR attendance
* Online payments
* Library systems
* External verification
* University APIs
* Advanced analytics
* AI-powered services

The immediate goal, however, remains simple:

> **Build a reliable core platform first. Expand only after the core workflow works.**

---

# 50. Recommended First Milestone

Before writing the first feature, the team should produce these six artifacts:

### 1. Product Requirements Document

Defines exactly what UniCore must do.

### 2. User Stories

Examples:

> As a student, I want to view my timetable so that I know when and where my classes occur.

> As a lecturer, I want to record attendance so that student attendance is automatically tracked.

> As an administrator, I want to manage courses so that students can register for available courses.

### 3. Use Case Diagram

Shows interactions between:

* Student
* Lecturer
* Administrator
* Super Admin

### 4. ERD

Defines the complete database structure.

### 5. System Architecture

Defines:

```text
Vue
 ↓
Laravel API
 ↓
Services
 ↓
Models
 ↓
PostgreSQL
```

### 6. UI Design System

Defines:

* Colors
* Typography
* Components
* Tables
* Forms
* Dashboards
* Navigation
* Responsive behavior

Only after these are approved should implementation begin.

---

# Project Summary

| Item           | Decision                                   |
| -------------- | ------------------------------------------ |
| Project        | UniCore                                    |
| Type           | University Digital Administration Platform |
| Target         | Cambodian universities                     |
| Team           | Rin Nairith + Lyhor                        |
| Architecture   | MVC / Modular Monolith                     |
| Backend        | Laravel 12                                 |
| Frontend       | Vue 3 + TypeScript                         |
| Styling        | Tailwind CSS                               |
| State          | Pinia                                      |
| Database       | PostgreSQL                                 |
| API            | REST                                       |
| Authentication | Student ID + Password                      |
| Authorization  | RBAC                                       |
| Notifications  | Email + Telegram                           |
| Payment        | Invoice + Payment Records                  |
| Online Payment | Not MVP                                    |
| Mobile App     | Future                                     |
| AI             | Future                                     |
| Deployment     | Docker + Laravel Cloud                     |
| Development    | Agile                                      |
| MVP Target     | ~12 weeks                                  |
| Initial Goal   | Production-capable MVP                     |
| Long-term Goal | Complete university digital ecosystem      |