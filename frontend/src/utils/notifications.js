// Shared helpers for the in-app inbox (docs/42_In-App-Notification-Inbox-Report.md).
import { Bell, Briefcase, CalendarCheck, ClipboardList, FileText, GraduationCap, Megaphone, Receipt, ShieldCheck } from '@lucide/vue'

// One Lucide icon per message kind; the colour stays the primary tint for all.
export const kindIcon = (kind) => ({
  announcement: Megaphone,
  assignment: ClipboardList,
  document: FileText,
  enrollment: CalendarCheck,
  grade: GraduationCap,
  internship: Briefcase,
  finance: Receipt,
  security: ShieldCheck,
})[kind] ?? Bell

const relative = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' })
const STEPS = [
  ['minute', 60],
  ['hour', 60 * 60],
  ['day', 60 * 60 * 24],
]

// "just now", "5 minutes ago", "yesterday"; a date after a week.
export const sentAgo = (iso) => {
  if (!iso) return ''
  const seconds = Math.round((Date.now() - new Date(iso).getTime()) / 1000)
  if (seconds < 60) return 'just now'
  if (seconds >= 60 * 60 * 24 * 7) return new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
  const [unit, size] = [...STEPS].reverse().find(([, step]) => seconds >= step)
  return relative.format(-Math.floor(seconds / size), unit)
}

// Badge text for the top-bar bell.
export const unreadLabel = (count) => (count > 9 ? '9+' : String(count))
