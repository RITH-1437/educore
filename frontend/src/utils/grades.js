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

// ---------------------------------------------------------------- grading scale
// Bands as the server stores them (GradingService::saveScale): sorted from the top
// grade down, each ending 0.01 below the next band's start, the top one at 100%.
// Incomplete rows (no valid start) are left out; `index` points back to the input row.
export const scaleRanges = (bands) => {
  const sorted = bands
    .map((band, index) => ({ ...band, index, min: Number(band.min_percentage), points: Number(band.grade_point) || 0 }))
    .filter((band) => band.min_percentage !== '' && band.min_percentage !== null && Number.isFinite(band.min))
    .sort((a, b) => b.min - a.min)
  return sorted.map((band, i) => ({ ...band, max: i === 0 ? 100 : Math.round((sorted[i - 1].min - 0.01) * 100) / 100 }))
}

// One hue, darker = more grade points (a sequential ramp); text colour per step keeps
// contrast. A failing band takes the error tint — always shown with its "Fail" label.
const BAND_TONES = [
  'bg-primary/15 text-ink dark:bg-dark-primary/20 dark:text-dark-ink',
  'bg-primary/30 text-ink dark:bg-dark-primary/35 dark:text-dark-ink',
  'bg-primary/45 text-ink dark:bg-dark-primary/50 dark:text-dark-ink',
  'bg-primary/80 text-white dark:bg-dark-primary/75 dark:text-dark-bg',
  'bg-primary text-white dark:bg-dark-primary dark:text-dark-bg',
]
export const bandTone = (band, maxPoints) => {
  if (!band.is_pass) return 'bg-error/15 text-error dark:bg-error/20 dark:text-red-300'
  const level = maxPoints > 0 ? Math.max(0, Number(band.grade_point ?? band.points) || 0) / maxPoints : 0
  return BAND_TONES[Math.min(BAND_TONES.length - 1, Math.round(level * (BAND_TONES.length - 1)))]
}

// The same rules the server applies (GradingScaleRequest + GradingService::saveScale),
// so the editor can explain a problem before saving. An empty list means "ready".
export const scaleIssues = (bands) => {
  const issues = []
  const blank = (value) => value === '' || value === null || value === undefined
  if (bands.length < 2) issues.push('Keep at least two bands.')
  if (bands.length > 20) issues.push('Use at most 20 bands.')
  if (bands.some((band) => blank(String(band.grade ?? '').trim()) || blank(band.min_percentage) || blank(band.grade_point))) {
    issues.push('Every band needs a grade, a starting percentage and grade points.')
  }
  if (bands.some((band) => String(band.grade ?? '').trim().length > 5)) issues.push('A grade is at most 5 characters.')
  const letters = bands.map((band) => String(band.grade ?? '').trim().toLowerCase()).filter(Boolean)
  if (new Set(letters).size !== letters.length) issues.push('Each grade can be used once.')
  const starts = bands.filter((band) => !blank(band.min_percentage)).map((band) => Number(band.min_percentage))
  if (new Set(starts).size !== starts.length) issues.push('Two bands cannot start at the same percentage.')
  if (starts.some((value) => !Number.isFinite(value) || value < 0 || value > 100)) issues.push('Percentages run from 0 to 100.')
  if (bands.some((band) => !blank(band.grade_point) && !(Number(band.grade_point) >= 0 && Number(band.grade_point) <= 5))) issues.push('Grade points run from 0 to 5.')
  const ranges = scaleRanges(bands)
  if (ranges.length && ranges[ranges.length - 1].min !== 0) issues.push('The lowest band must start at 0%.')
  for (let i = ranges.length - 1; i > 0; i--) {
    if (ranges[i - 1].points < ranges[i].points) issues.push(`${ranges[i - 1].grade || 'A band'} starts higher than ${ranges[i].grade || 'the band below'} but has fewer grade points.`)
  }
  return issues
}
