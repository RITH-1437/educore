// "Wed, 15 Apr 2026 · 09:00–11:00 · Hall A" — dates are plain Y-m-d (no timezone shift).
export const examWhen = (exam) => {
  if (!exam.scheduled_date) return 'Date to be announced'
  const [y, m, d] = exam.scheduled_date.split('-').map(Number)
  const date = new Date(y, m - 1, d).toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
  const time = exam.start_time ? ` · ${exam.start_time}–${exam.end_time}` : ''
  return `${date}${time}${exam.location ? ` · ${exam.location}` : ''}`
}

export const typeLabel = (value) => value.charAt(0).toUpperCase() + value.slice(1)
