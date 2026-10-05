import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
  Award,
  BarChart3,
  BookMarked,
  Briefcase,
  Building2,
  Calendar,
  CalendarClock,
  ClipboardList,
  FileCheck,
  FileText,
  FileWarning,
  GraduationCap,
  LayoutDashboard,
  Library,
  ListChecks,
  Megaphone,
  MapPin,
  Receipt,
  Scale,
  School,
  ShieldCheck,
  TableProperties,
  UserCheck,
  UserRound,
  UsersRound,
} from '@lucide/vue'

// Mirrors the route-level role middleware in backend/routes/web.php. This only
// decides what is *shown*; the server remains the enforcement point.
const navForRole = (role) => {
  if (role === 'super-admin') {
    return [
      { label: 'Overview', items: [{ label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard }, { label: 'Analytics', href: '/analytics', icon: BarChart3 }] },
      {
        label: 'Academic structure',
        items: [
          { label: 'University', href: '/universities', icon: Building2 },
          { label: 'Departments', href: '/departments', icon: School },
          { label: 'Programs', href: '/programs', icon: BookMarked },
          { label: 'Academic years', href: '/academic-years', icon: Calendar },
        ],
      },
      {
        label: 'People',
        items: [
          { label: 'Users & roles', href: '/users', icon: UsersRound },
          { label: 'Students', href: '/students', icon: GraduationCap },
          { label: 'Lecturers', href: '/lecturers', icon: UserRound },
        ],
      },
      { label: 'Academics', items: [{ label: 'Courses', href: '/courses', icon: Library }, { label: 'Offerings & sections', href: '/offerings', icon: TableProperties }, { label: 'Enrollments', href: '/enrollments', icon: ListChecks }, { label: 'Rooms', href: '/rooms', icon: MapPin }, { label: 'Grades', href: '/grades', icon: Award }, { label: 'Grading scale', href: '/grading-scale', icon: Scale }] },
      { label: 'Operations', items: [{ label: 'Announcements', href: '/announcements', icon: Megaphone }, { label: 'Documents', href: '/documents', icon: FileText }, { label: 'Document types', href: '/document-types', icon: FileCheck }, { label: 'Internships', href: '/internships', icon: Briefcase }, { label: 'Invoices', href: '/invoices', icon: Receipt }] },
      { label: 'System', items: [{ label: 'Audit logs', href: '/audit-logs', icon: ShieldCheck }, { label: 'Error logs', href: '/error-logs', icon: FileWarning }] },
    ]
  }

  if (['university-admin', 'department-admin'].includes(role)) {
    const structure = [
      { label: 'University', href: '/universities', icon: Building2 },
      { label: 'Departments', href: '/departments', icon: School },
      { label: 'Programs', href: '/programs', icon: BookMarked },
      { label: 'Courses', href: '/courses', icon: Library },
      { label: 'Offerings & sections', href: '/offerings', icon: TableProperties },
      { label: 'Enrollments', href: '/enrollments', icon: ListChecks },
      { label: 'Rooms', href: '/rooms', icon: MapPin },
    ]
    // Academic calendar management is limited to university admins.
    if (role === 'university-admin') structure.push({ label: 'Academic years', href: '/academic-years', icon: Calendar })

    return [
      // Analytics is institution-wide, so University Admin only (not Department Admin).
      { label: 'Overview', items: [{ label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard }, ...(role === 'university-admin' ? [{ label: 'Analytics', href: '/analytics', icon: BarChart3 }] : [])] },
      { label: 'Academic structure', items: structure },
      { label: 'People', items: [{ label: 'Students', href: '/students', icon: GraduationCap }, { label: 'Lecturers', href: '/lecturers', icon: UserRound }] },
      { label: 'Assessment', items: [{ label: 'Grades', href: '/grades', icon: Award }, { label: 'Grading scale', href: '/grading-scale', icon: Scale }] },
      // Finance and type configuration are limited to university admins (Department Admin has no access).
      { label: 'Operations', items: [{ label: 'Announcements', href: '/announcements', icon: Megaphone }, { label: 'Documents', href: '/documents', icon: FileText }, ...(role === 'university-admin' ? [{ label: 'Document types', href: '/document-types', icon: FileCheck }] : []), { label: 'Internships', href: '/internships', icon: Briefcase }, ...(role === 'university-admin' ? [{ label: 'Invoices', href: '/invoices', icon: Receipt }] : [])] },
    ]
  }

  if (role === 'student') {
    return [{ label: 'Workspace', items: [
      { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
      { label: 'Course registration', href: '/registration', icon: ListChecks },
      { label: 'My timetable', href: '/timetable', icon: CalendarClock },
      { label: 'My attendance', href: '/my-attendance', icon: UserCheck },
      { label: 'My assignments', href: '/my-assignments', icon: ClipboardList },
      { label: 'My exams', href: '/my-exams', icon: FileCheck },
      { label: 'Grades & GPA', href: '/my-grades', icon: Award },
      { label: 'My documents', href: '/my-documents', icon: FileText },
      { label: 'My invoices', href: '/my-invoices', icon: Receipt },
      { label: 'My internship', href: '/my-internships', icon: Briefcase },
      { label: 'Announcements', href: '/announcements', icon: Megaphone },
    ] }]
  }

  if (role === 'lecturer') {
    return [{ label: 'Workspace', items: [
      { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
      { label: 'My timetable', href: '/timetable', icon: CalendarClock },
      { label: 'Attendance', href: '/attendance', icon: UserCheck },
      { label: 'Grading scale', href: '/grading-scale', icon: Scale },
      { label: 'Announcements', href: '/announcements', icon: Megaphone },
    ] }]
  }

  return [{ label: 'Workspace', items: [{ label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard }] }]
}

const SECTION_LABELS = {
  users: 'Users & roles',
  'academic-years': 'Academic years',
  universities: 'University',
  departments: 'Departments',
  programs: 'Programs',
  courses: 'Courses',
  lecturers: 'Lecturers',
  students: 'Students',
  offerings: 'Offerings & sections',
  enrollments: 'Enrollments',
  registration: 'Course registration',
  rooms: 'Rooms',
  timetable: 'My timetable',
  attendance: 'Attendance',
  'my-attendance': 'My attendance',
  'my-assignments': 'My assignments',
  coursework: 'Coursework',
  'my-exams': 'My exams',
  exams: 'Examinations',
  'my-grades': 'Grades & GPA',
  grades: 'Grades',
  'grading-scale': 'Grading scale',
  'my-documents': 'My documents',
  documents: 'Documents',
  'document-types': 'Document types',
  invoices: 'Invoices',
  announcements: 'Announcements',
  analytics: 'Analytics',
  notifications: 'Notification settings',
  inbox: 'Notifications',
  account: 'Account',
  'my-invoices': 'My invoices',
  internships: 'Internships',
  'my-internships': 'My internship',
  'internship-companies': 'Internship companies',
  'error-logs': 'Error logs',
  'audit-logs': 'Audit logs',
}

const ACTION_LABELS = { create: 'New', edit: 'Edit' }

export function useNavigation() {
  const page = usePage()
  const role = computed(() => page.props.auth?.user?.role?.slug ?? '')
  const path = computed(() => page.url.split('?')[0])

  const navGroups = computed(() => navForRole(role.value))
  const homeHref = computed(() => (role.value === 'super-admin' ? '/admin/dashboard' : '/dashboard'))

  const isActive = (href) => path.value === href || path.value.startsWith(`${href}/`)

  // Dashboard → section → (New | Edit | Details)
  const breadcrumbs = computed(() => {
    const [section, detail, action] = path.value.split('/').filter(Boolean)
    const trail = [{ label: 'Dashboard', href: homeHref.value }]
    if (!section || section === 'dashboard' || section === 'admin') return trail

    trail.push({ label: SECTION_LABELS[section] ?? 'Overview', href: `/${section}` })
    if (detail) trail.push({ label: ACTION_LABELS[detail] ?? ACTION_LABELS[action] ?? 'Details', href: '' })
    return trail
  })

  const pageTitle = computed(() => breadcrumbs.value[1]?.label ?? 'Dashboard')

  return { role, navGroups, homeHref, isActive, breadcrumbs, pageTitle }
}
