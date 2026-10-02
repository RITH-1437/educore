// Shared helpers for document screens (modules 9.16 / 9.17).

// Request status → StatusBadge status + label.
export const requestBadge = (status) => ({
  pending: { status: 'pending', label: 'Pending' },
  approved: { status: 'active', label: 'Approved' },
  rejected: { status: 'rejected', label: 'Rejected' },
  generated: { status: 'completed', label: 'Ready' },
})[status] ?? { status, label: status }

export const documentBadge = (status) => ({
  valid: { status: 'completed', label: 'Valid' },
  revoked: { status: 'failed', label: 'Revoked' },
  expired: { status: 'inactive', label: 'Expired' },
})[status] ?? { status, label: status }

export const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }) : '—')

export const fileSize = (bytes) => (bytes ? `${Math.max(1, Math.round(bytes / 1024))} KB` : '')
