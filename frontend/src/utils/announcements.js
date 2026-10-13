// Shared helpers for announcement screens (module 9.19).

export const typeLabel = (type) => ({ general: 'General', academic: 'Academic', administrative: 'Administrative', event: 'Event' })[type] ?? 'General'

// Category badge variants stay within the design system (no per-type colours).
export const typeVariant = (type) => ({ academic: 'primary', event: 'success', administrative: 'warning' })[type] ?? 'muted'

export const stateBadge = (state) => ({
  draft: { status: 'draft', label: 'Draft' },
  scheduled: { status: 'scheduled', label: 'Scheduled' },
  published: { status: 'published', label: 'Published' },
  archived: { status: 'archived', label: 'Archived' },
})[state] ?? { status: state, label: state }

export const AUDIENCES = [
  { value: 'all', label: 'Everyone', group: true },
  { value: 'students', label: 'All students', group: true },
  { value: 'lecturers', label: 'All lecturers', group: true },
  { value: 'staff', label: 'Administrative staff', group: true },
  { value: 'department', label: 'A department' },
  { value: 'program', label: 'A program' },
  { value: 'section', label: 'A section' },
  { value: 'course', label: 'A course' },
]

// `datetime-local` value (the browser's wall clock) <-> ISO instant; the server
// stores UTC and every screen shows the reader's local time.
export const toInstant = (local) => (local ? new Date(local).toISOString() : null)
export const localInputValue = (date) => {
  const d = new Date(date.getTime() - date.getTimezoneOffset() * 60000)
  return d.toISOString().slice(0, 16)
}

// "Read by 12 of 340" — live counts for the current audience (report 51).
export const reach = (item) => {
  if (item.audience_size === undefined) return ''
  const people = `${item.audience_size} ${item.audience_size === 1 ? 'person' : 'people'}`
  return item.publish_state === 'draft' || item.publish_state === 'scheduled'
    ? `reaches ${people}`
    : `read by ${item.read_count} of ${people}`
}

export const when = (iso) => (iso ? new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '')
