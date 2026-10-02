// Shared helpers for internship screens (module 9.22).

// Status → StatusBadge status + label (semantic colours only).
export const statusBadge = (status) => ({
  draft: { status: 'draft', label: 'Draft' },
  submitted: { status: 'pending', label: 'Submitted' },
  under_review: { status: 'pending', label: 'Under review' },
  approved: { status: 'approved', label: 'Approved' },
  rejected: { status: 'rejected', label: 'Rejected' },
  in_progress: { status: 'active', label: 'In progress' },
  completed: { status: 'completed', label: 'Completed' },
  cancelled: { status: 'cancelled', label: 'Cancelled' },
})[status] ?? { status, label: status }

export const FINAL = ['rejected', 'completed', 'cancelled']

export const reportTypeLabel = (type) => ({ initial: 'Initial', progress: 'Progress', final: 'Final' })[type] ?? type

export const ratingLabel = (rating) => (rating ? rating.replace('_', ' ').replace(/^./, (c) => c.toUpperCase()) : '—')

export const formatDate = (value) => (value ? new Date(value).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : '—')
