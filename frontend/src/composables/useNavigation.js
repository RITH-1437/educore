import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
  BookOpen,
  CalendarDays,
  GraduationCap,
  Landmark,
  Layers,
  LayoutDashboard,
  LayoutGrid,
  Presentation,
  ScrollText,
  UserRound,
  Users,
} from '@lucide/vue'

// Mirrors the route-level role middleware in backend/routes/web.php. This only
// decides what is *shown*; the server remains the enforcement point.
const navForRole = (role) => {
  if (role === 'super-admin') {
    return [
      { label: 'Overview', items: [{ label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard }] },
      {
        label: 'Academic structure',
        items: [
          { label: 'University', href: '/universities', icon: Landmark },
          { label: 'Faculties & departments', href: '/faculties', icon: GraduationCap },
          { label: 'Programs', href: '/programs', icon: Layers },
          { label: 'Academic years', href: '/academic-years', icon: CalendarDays },
        ],
      },
      {
        label: 'People',
        items: [
          { label: 'Users & roles', href: '/users', icon: Users },
          { label: 'Students', href: '/students', icon: UserRound },
          { label: 'Lecturers', href: '/lecturers', icon: Presentation },
        ],
      },
      { label: 'Academics', items: [{ label: 'Courses', href: '/courses', icon: BookOpen }, { label: 'Offerings & sections', href: '/offerings', icon: LayoutGrid }] },
      { label: 'System', items: [{ label: 'Error logs', href: '/error-logs', icon: ScrollText }] },
    ]
  }

  if (['university-admin', 'faculty-admin'].includes(role)) {
    const structure = [
      { label: 'University', href: '/universities', icon: Landmark },
      { label: 'Faculties & departments', href: '/faculties', icon: GraduationCap },
      { label: 'Programs', href: '/programs', icon: Layers },
      { label: 'Courses', href: '/courses', icon: BookOpen },
      { label: 'Offerings & sections', href: '/offerings', icon: LayoutGrid },
    ]
    // Academic calendar management is limited to university admins.
    if (role === 'university-admin') structure.push({ label: 'Academic years', href: '/academic-years', icon: CalendarDays })

    return [
      { label: 'Overview', items: [{ label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard }] },
      { label: 'Academic structure', items: structure },
      { label: 'People', items: [{ label: 'Students', href: '/students', icon: UserRound }, { label: 'Lecturers', href: '/lecturers', icon: Presentation }] },
    ]
  }

  return [{ label: 'Workspace', items: [{ label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard }] }]
}

const SECTION_LABELS = {
  users: 'Users & roles',
  'academic-years': 'Academic years',
  universities: 'University',
  faculties: 'Faculties & departments',
  programs: 'Programs',
  courses: 'Courses',
  lecturers: 'Lecturers',
  students: 'Students',
  offerings: 'Offerings & sections',
  'error-logs': 'Error logs',
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
