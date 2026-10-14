import {
  Award,
  BarChart3,
  Briefcase,
  Building2,
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
  ShieldCheck,
  UserCheck,
} from '@lucide/vue'

// Landing-page product previews. Layouts mirror the real dashboards
// (pages/UniversityAdmin, pages/Lecturer, pages/Student).
// - Admin: live figures from the `stats` prop (LandingStatsService, the same
//   numbers as the University Admin dashboard). Each stat's `value` is a
//   function of those stats; an empty database shows zeros and dashes.
// - Lecturer / Student: personal dashboards, so they show demo data — labelled
//   as such in the preview — instead of anyone's real record.
const n = (value) => (value === null || value === undefined ? '—' : Number(value).toLocaleString())
const pct = (value) => (value === null || value === undefined ? '—' : `${value}%`)

export const previews = {
  admin: {
    tab: 'University administration',
    title: 'University Admin dashboard',
    summary: 'University Admins see what is waiting for action across the institution and the figures that matter this semester.',
    capabilities: [
      'Approve document requests and internship applications',
      'Follow invoices, payments and overdue balances',
      'Review enrollment, attendance and results in Analytics',
    ],
    live: true,
    caption: 'Live figures from this installation — the same numbers as the University Admin dashboard.',
    nav: [
      { icon: LayoutDashboard, label: 'Dashboard' },
      { icon: BarChart3, label: 'Analytics' },
      { icon: Building2, label: 'Departments' },
      { icon: GraduationCap, label: 'Students' },
      { icon: Library, label: 'Courses' },
      { icon: FileText, label: 'Documents' },
      { icon: Receipt, label: 'Invoices' },
      { icon: ShieldCheck, label: 'Audit logs' },
    ],
    eyebrow: 'University dashboard',
    heading: 'University Admin',
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
    tab: 'Lecturer workspace',
    title: 'Lecturer dashboard',
    summary: "Lecturers start the day with their classes, the registers still to take, the submissions to grade and the state of each grade sheet.",
    capabilities: [
      'Take attendance for every class session',
      'Grade submissions and enter exam results',
      'Submit grade sheets for approval',
    ],
    caption: 'Demo data — each lecturer sees their own classes and sections after signing in.',
    nav: [
      { icon: LayoutDashboard, label: 'Dashboard' },
      { icon: CalendarClock, label: 'My timetable' },
      { icon: UserCheck, label: 'Attendance' },
      { icon: ClipboardList, label: 'Coursework' },
      { icon: FileCheck, label: 'Exams' },
      { icon: Award, label: 'Grades' },
      { icon: Megaphone, label: 'Announcements' },
    ],
    eyebrow: 'Lecturer dashboard',
    heading: 'Teaching at a glance',
    description: 'Semester 1 · 2026–2027',
    groups: [
      {
        heading: '',
        stats: [
          { label: 'Classes today', value: '3', detail: 'In your timetable' },
          { label: 'Registers to take', value: '1', detail: 'Past classes' },
          { label: 'Submissions to grade', value: '12', detail: 'Submitted or late' },
          { label: 'Upcoming exams', value: '2', detail: 'Scheduled' },
        ],
      },
    ],
    panels: [
      {
        title: "Today's classes",
        rows: [
          { title: '08:00 · CS201 Data Structures', meta: 'Section A · Room B-204', badge: 'Register taken', variant: 'success' },
          { title: '10:30 · CS210 Database Systems', meta: 'Section B · Lab 3', badge: 'Next', variant: 'primary' },
          { title: '15:00 · CS101 Programming I', meta: 'Section C · Room A-105', badge: 'Later', variant: 'muted' },
        ],
      },
      {
        title: 'Grade sheets',
        rows: [
          { title: 'CS201 · Section A', meta: '42 students', badge: 'Draft', variant: 'muted' },
          { title: 'CS210 · Section B', meta: '38 students', badge: 'Submitted', variant: 'warning' },
          { title: 'CS101 · Section C', meta: '45 students', badge: 'Approved', variant: 'success' },
        ],
      },
    ],
  },
  student: {
    tab: 'Student workspace',
    title: 'Student dashboard',
    summary: 'Students see their GPA, courses, attendance, classes and exams in one place, and handle requests without visiting an office.',
    capabilities: [
      'Register for sections with automatic checks',
      'Follow attendance, grades and GPA per course',
      'Request transcripts and certificates online',
    ],
    caption: 'Demo data — each student sees their own record after signing in.',
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
    heading: 'Your academic overview',
    description: 'BSc Computer Science · Year 2',
    groups: [
      {
        heading: '',
        stats: [
          { label: 'Cumulative GPA', value: '3.42', detail: 'Weighted by credits' },
          { label: 'Credits this semester', value: '18', detail: '6 courses' },
          { label: 'Attendance', value: '94%', detail: 'This semester' },
          { label: 'Credits earned', value: '72', detail: 'All semesters' },
        ],
      },
    ],
    panels: [
      {
        title: 'Upcoming exams',
        rows: [
          { title: 'CS210 Database Systems', meta: 'Midterm · Mon 09:00 · Hall 1', badge: 'Midterm', variant: 'muted' },
          { title: 'MA202 Linear Algebra', meta: 'Quiz · Wed 14:00 · Room A-105', badge: 'Quiz', variant: 'muted' },
        ],
      },
      {
        title: 'Recent grades',
        rows: [
          { title: 'CS101 Programming I', meta: 'Semester 2 · 2025–2026', badge: 'A', variant: 'primary' },
          { title: 'MA101 Calculus I', meta: 'Semester 2 · 2025–2026', badge: 'B+', variant: 'primary' },
        ],
      },
    ],
  },
}
