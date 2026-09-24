---
name: educore-timetable
description: EduCore timetable - section scheduling, conflict detection (lecturer/room/section), and API/UI/authorization. Consult for any schedule work.
---

# EduCore — Timetable

## 1. Purpose

Define when/where each section meets (days, time slots, rooms) and detect
conflicts (lecturer, room, student group).

## 2. Main entities

- `schedule` entries: section, day_of_week, start_time, end_time, room.
- `room` — physical room (code, capacity, building).
- Recurrences: a schedule entry is a recurring weekly slot (day + time) rather
  than individual dated meetings, unless a dated model is already established.

## 3. Relationships

- Section **has many** Schedule entries (1–N).
- Schedule → Room (N–1).
- Room **has many** Schedule entries across sections.
- Lecturer ↔ Sections (assignment) — timetable must respect it.

## 4. Business rules (critical)

- **No lecturer conflict**: a lecturer cannot teach two sections at the same
  day/time.
- **No room conflict**: a room cannot host two sections at the same day/time.
- **No section conflict**: a section cannot have two different meetings at the
  same day/time.
- **No student conflict**: two sections enrolled by the SAME students must not
  overlap in time (validate at enrollment too — see `enrollment`).
- Rooms have capacity ≥ section enrollments.
- Same-day/time slots fit within semester day ranges; follow institutional
  time-slot conventions.

## 5. API responsibilities

- `GET /api/sections/{section}/schedule`, `POST/PATCH/DELETE .../schedule`.
- `GET /api/rooms`, `POST/PATCH/DELETE /api/rooms`.
- `GET /api/timetable/student/{student}` (or own) — the student's weekly grid.
- `GET /api/timetable/lecturer/{lecturer}` — teaching grid.
- Optional room availability endpoint for scheduling UI.

## 6. Backend responsibilities

- `TimetableService` centralizes conflict checks (lecturer/room/section); called
  by schedule CRUD and by enrollment when adding to a section.
- Use unique DB constraints on `(room_id, day_of_week, start_time, end_time)`
  and `(section_id, day_of_week, start_time)` as a backstop.
- Validate exclusive time-ranges (no partial overlap, not just equal times).
- Return schedule as an ordered weekly grid for the UI (Mon–Sun / slots).

## 7. Frontend responsibilities

- Weekly timetable view for students/lecturers (grid by day × time).
- Admin scheduling screen: pick section, room, day, time — show conflicts before
  submit (server still validates).
- Room management CRUD.

## 8. Authorization rules

- Univ/Faculty/Dept Admin: create rooms & schedules; publish timetables.
- Lecturer: view own timetable (and sections they teach).
- Student: view own timetable.

## 9. Validation rules

- Room exists + capacity; times valid (begin < end, within day); days valid;
  no overlap (per rules above).

## 10. Important edge cases

- Partial overlap (10:00–12:00 vs 11:00–13:00) — must be rejected.
- Scheduling a room bigger than needed or smaller than the section — capacity
  guidance.
- Student-group conflict only matters for shared enrollments — check the
  overlapping section's enrollment sets.
- Deleting/changing a room mid-semester — allow but warn; keep history.

## 11. Testing requirements

- Lecturer/room/section overlap rejected (incl. partial overlap).
- Capacity room ≥ enrollments.
- Student timetable grid correctness.
- Enrollment blocked when it creates a student conflict.

## 12. Must NOT

- Must NOT allow double-booking any of lecturer/room/section.
- Must NOT place timetable logic only in the frontend.
- Must NOT invent "classroom" as separate from Room, or "subject" for course.

## Cross-references

- `skills/academic-domain/SKILL.md`, `skills/course-management/SKILL.md`,
  `skills/enrollment/SKILL.md`, `skills/database/SKILL.md`.

## Agent behavior (mandatory)

1. Inspect existing implementation first. 2. Follow established conventions.
3. Do not rewrite working code. 4. No tech outside the stack. 5. No unnecessary
abstractions. 6. No duplicate business logic. 7. No invented relationships.
8. No bypassing authorization. 9. No hardcoded secrets. 10. No unrelated
module changes. 11. Run tests. 12. Explain architectural decisions.