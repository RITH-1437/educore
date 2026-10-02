// Shared helpers for finance screens (module 9.18). Display only — every
// amount shown comes from the server; the browser never decides a total.

export const money = (amount, currency = 'USD') => {
  const value = Number(amount ?? 0)
  return currency === 'KHR'
    ? `${Math.round(value).toLocaleString()} ៛`
    : value.toLocaleString(undefined, { style: 'currency', currency, minimumFractionDigits: 2 })
}

// Invoice status → StatusBadge status + label (paid = success, overdue = error,
// pending / partial = warning, cancelled = muted).
export const invoiceBadge = (status) => ({
  pending: { status: 'pending', label: 'Pending' },
  partial: { status: 'pending', label: 'Partially paid' },
  paid: { status: 'completed', label: 'Paid' },
  overdue: { status: 'failed', label: 'Overdue' },
  cancelled: { status: 'cancelled', label: 'Cancelled' },
})[status] ?? { status, label: status }

export const methodLabel = (method) => ({ cash: 'Cash', bank_transfer: 'Bank transfer', cheque: 'Cheque', other: 'Other' })[method] ?? method

export const categoryLabel = (category) => (category ? category.charAt(0).toUpperCase() + category.slice(1) : '—')
