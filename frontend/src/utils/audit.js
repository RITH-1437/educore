// Helpers for the audit trail screens (module 9.24).

// "grades.approved" → "Grades approved"; "document_request.rejected" → "Document request rejected".
export const actionLabel = (action) => {
  const text = action.replaceAll('_', ' ').replace('.', ' ')
  return text.charAt(0).toUpperCase() + text.slice(1)
}

export const when = (iso) => (iso ? new Date(iso).toLocaleString(undefined, { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '')

// Render a snapshot value: scalars as-is, objects / arrays as indented JSON.
export const show = (value) => {
  if (value === undefined) return '—'
  if (value === null) return 'null'
  return typeof value === 'object' ? JSON.stringify(value, null, 2) : String(value)
}
