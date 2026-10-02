// Shared labels for Grades & GPA screens (module 9.14).

export const COMPONENTS = ['attendance', 'assignment', 'midterm', 'final', 'practical']

const LABELS = {
  attendance: 'Attendance',
  assignment: 'Coursework',
  midterm: 'Midterm',
  final: 'Final',
  practical: 'Practical',
}

export const componentLabel = (component) => LABELS[component] ?? component

export const percent = (value) => (value === null || value === undefined ? '—' : `${Number(value).toFixed(1)}%`)

export const gpa = (value) => (value === null || value === undefined ? '—' : Number(value).toFixed(2))

// Grade status → StatusBadge status (approved = success, submitted = pending, draft = muted).
export const gradeStatus = (status) => ({ submitted: 'pending' })[status] ?? status

export const gradeStatusLabel = (status) => ({ draft: 'Draft', submitted: 'Awaiting approval', approved: 'Approved', finalized: 'Finalized' })[status] ?? status
