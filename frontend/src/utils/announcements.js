// Shared helpers for announcement screens (module 9.19).

export const typeLabel = (type) => ({ general: 'General', academic: 'Academic', administrative: 'Administrative', event: 'Event' })[type] ?? 'General'

// Category badge variants stay within the design system (no per-type colours).
export const typeVariant = (type) => ({ academic: 'primary', event: 'success', administrative: 'warning' })[type] ?? 'muted'

export const stateBadge = (state) => ({
  draft: { status: 'draft', label: 'Draft' },
  published: { status: 'published', label: 'Published' },
  archived: { status: 'archived', label: 'Archived' },
})[state] ?? { status: state, label: state }

export const AUDIENCES = [
  { value: 'all', label: 'Everyone', group: true },
  { value: 'students', label: 'All students', group: true },
  { value: 'lecturers', label: 'All lecturers', group: true },
  { value: 'staff', label: 'Administrative staff', group: true },
  { value: 'faculty', label: 'A faculty' },
  { value: 'department', label: 'A department' },
  { value: 'program', label: 'A program' },
  { value: 'section', label: 'A section' },
  { value: 'course', label: 'A course' },
]

export const when = (iso) => (iso ? new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '')
