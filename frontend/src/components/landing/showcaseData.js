import {
  Award,
  BarChart3,
  Briefcase,
  CalendarClock,
  ClipboardList,
  FileCheck,
  FileText,
  GraduationCap,
  LayoutDashboard,
  Library,
  ListChecks,
  Megaphone,
  Receipt,
  School,
  ShieldCheck,
  UserCheck,
} from '@lucide/vue'

// Landing-page product previews. Layouts mirror the real dashboards
// (pages/UniversityAdmin, pages/Lecturer, pages/Student).
// - Admin: live figures from the `stats` prop (LandingStatsService, the same
//   numbers as the University Admin dashboard). Each stat's `value` is a
//   function of those stats; an empty database shows zeros and dashes.
// - Lecturer / Student: personal dashboards, so no values (rendered as
//   placeholders) — each person sees their own data after signing in.
const n = (value) => (value === null || value === undefined ? '—' : Number(value).toLocaleString())
const pct = (value) => (value === null || value === undefined ? '—' : `${value}%`)

export const previews = {
  admin: {
    tab: 'Admin',
    summary: 'University Admins see what is waiting for action across the institution, and the figures that matter this semester.',
    nav: [
      { icon: LayoutDashboard, label: 'Dashboard' },
      { icon: BarChart3, label: 'Analytics' },
      { icon: School, label: 'Faculties' },
      { icon: GraduationCap, label: 'Students' },
      { icon: Library, label: 'Courses' },
      { icon: FileText, label: 'Documents' },
      { icon: Receipt, label: 'Invoices' },
      { icon: ShieldCheck, label: 'Audit logs' },
    ],
    eyebrow: 'University dashboard',
    title: 'Welcome back, Admin',
    description: (stats) => (stats?.semester ? `Current academic period: ${stats.semester.name}` : 'No active semester yet'),
    groups: [
      {
        heading: 'Waiting for action',
        stats: [
          { label: 'Requests to approve', value: (s) => n(s?.waiting?.document_requests_pending), detail: 'Document requests' },
          { label: 'PDFs to generate', value: (s) => n(s?.waiting?.document_requests_approved), detail: 'Approved requests' },
          { label: 'Applications to review', value: (s) => n(s?.waiting?.internships_submitted), detail: 'Internships submitted' },
          { label: 'Overdue invoices', value: (s) => n(s?.waiting?.invoices_overdue), detail: 'Past due date' },
        ],
      },
      {
        heading: 'Academic overview',
        stats: [
          { label: 'Active students', value: (s) => n(s?.overview?.students_active), detail: 'All programs' },
          { label: 'Active lecturers', value: (s) => n(s?.overview?.lecturers_active), detail: 'All departments' },
          { label: 'Sections running', value: (s) => n(s?.overview?.sections), detail: 'This semester' },
          { label: 'Attendance rate', value: (s) => pct(s?.overview?.attendance_rate), detail: 'Semester average' },
        ],
      },
    ],
    panels: [],
  },
  lecturer: {
    tab: 'Lecturer',
    summary: 'Lecturers start the day with their classes, the registers still to take, the submissions to grade and the state of every grade sheet.',
    nav: [
      { icon: LayoutDashboard, label: 'Dashboard' },
      { icon: CalendarClock, label: 'My timetable' },
      { icon: UserCheck, label: 'Attendance' },
      { icon: ClipboardList, label: 'Assignments' },
      { icon: FileCheck, label: 'Exams' },
      { icon: Award, label: 'Grades' },
      { icon: Megaphone, label: 'Announcements' },
    ],
    eyebrow: 'Lecturer dashboard',
    title: 'Welcome back, Lecturer',
    description: 'Your classes, registers and grade sheets',
    personal: true,
    groups: [
      {
        heading: '',
        stats: [
          { label: 'Classes today', detail: 'In your timetable' },
          { label: 'Registers to take', detail: 'Without attendance' },
          { label: 'Submissions to grade', detail: 'Submitted or late' },
          { label: 'Upcoming exams', detail: 'Scheduled' },
        ],
      },
    ],
    panels: [
      {
        title: "Today's classes",
        rows: 3,
      },
      {
        title: 'Grade sheets',
        rows: 3,
      },
    ],
  },
  student: {
    tab: 'Student',
    summary: 'Students see their GPA, courses, attendance, classes and exams in one place, without visiting an office.',
    nav: [
      { icon: LayoutDashboard, label: 'Dashboard' },
      { icon: ListChecks, label: 'Course registration' },
      { icon: CalendarClock, label: 'My timetable' },
      { icon: UserCheck, label: 'My attendance' },
      { icon: Award, label: 'Grades & GPA' },
      { icon: FileText, label: 'My documents' },
      { icon: Briefcase, label: 'My internship' },
    ],
    eyebrow: 'Student dashboard',
    title: 'Welcome back, Student',
    description: 'Your program, courses and results',
    personal: true,
    groups: [
      {
        heading: '',
        stats: [
          { label: 'Cumulative GPA', detail: 'Weighted by credits' },
          { label: 'Courses', detail: 'This semester' },
          { label: 'Attendance', detail: 'This semester' },
          { label: 'Credits earned', detail: 'Across all semesters' },
        ],
      },
    ],
    panels: [
      {
        title: 'Upcoming classes',
        rows: 3,
      },
      {
        title: 'Upcoming exams',
        rows: 3,
      },
    ],
  },
}

export const previewRoles = ['admin', 'lecturer', 'student']
