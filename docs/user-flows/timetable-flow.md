# EduCore — Timetable Management & Conflict Detection Flow

This document details how class schedules, rooms, and weekly timetables are configured, and explains the 3-way conflict detection algorithm that guarantees room, instructor, and student consistency.

- **Status:** `[Implemented]`
- **Source Code Reference:** `TimetableController.php`, `TimetableService.php`, `backend/app/Models/ScheduleEntry.php`, `backend/routes/web.php`
- **PlantUML Equivalent:** [`puml/timetable-flow.puml`](puml/timetable-flow.puml)

---

## 1. 3-Way Schedule Conflict Detection Engine

When an administrator adds a weekly schedule slot to a section (`POST /sections/{id}/schedule`), EduCore evaluates three distinct physical and temporal constraints:

```mermaid
flowchart TD
    Start([Schedule Slot Input:<br/>Section ID, Day of Week, Start Time, End Time, Room ID]) --> RoomCheck{1. Room Conflict Check:<br/>Is Room already assigned to another<br/>section on same Day with overlapping time?}

    RoomCheck -- Conflict Found --> RoomErr[Abort 422: Room is already booked for that timeslot]
    RoomErr --> EndFail([Scheduling Rejected])

    RoomCheck -- Clear --> LectCheck{2. Lecturer Conflict Check:<br/>Is any Lecturer assigned to this section<br/>scheduled to teach another section at same time?}

    LectCheck -- Conflict Found --> LectErr[Abort 422: Lecturer has a schedule conflict]
    LectErr --> EndFail

    LectCheck -- Clear --> SecCheck{3. Section Overlap Check:<br/>Does this section already have another<br/>slot overlapping with this proposed time?}

    SecCheck -- Conflict Found --> SecErr[Abort 422: Section cannot have overlapping schedule slots]
    SecErr --> EndFail

    SecCheck -- Clear --> SaveEntry[DB Insert into schedule_entries table:<br/>section_id, room_id, day_of_week,<br/>start_time, end_time]
    SaveEntry --> EndSuccess([Timetable Slot Successfully Booked])
```

---

## 2. Timetable Generation Views

Once scheduled, weekly timetables are rendered dynamically based on the actor's context:

```mermaid
flowchart TD
    QueryTimetable([User requests /timetable]) --> CheckRole{User Role?}

    CheckRole -- Student --> StudentSchedule[Fetch all Sections where student is actively enrolled<br/>Join schedule_entries + rooms + lecturers<br/>Render 7-day grid with personal courses]
    
    CheckRole -- Lecturer --> LecturerSchedule[Fetch all Sections assigned to authenticated lecturer<br/>Join schedule_entries + rooms<br/>Render 7-day grid with assigned teaching slots]
    
    CheckRole -- Admin --> AdminSchedule[Select Department / Room / Lecturer filter<br/>Render master institutional timetable grid]

    StudentSchedule --> DisplayGrid[Render Interactive Timetable Grid]
    LecturerSchedule --> DisplayGrid
    AdminSchedule --> DisplayGrid
```
