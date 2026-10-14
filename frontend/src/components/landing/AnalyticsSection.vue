<script setup>
import { CircleCheck } from '@lucide/vue'
import { computed } from 'vue'
import BaseBadge from '../BaseBadge.vue'
import EmptyState from '../EmptyState.vue'
import BarChart from '../charts/BarChart.vue'
import PieChart from '../charts/PieChart.vue'
import Reveal from './Reveal.vue'

// Live, aggregate-only figures (LandingStatsService) on the definitions of the
// analytics page (module 9.23). When the installation has nothing to chart yet,
// the preview switches as a whole to sample figures, badged and captioned as
// sample data — live and sample numbers are never mixed.
const props = defineProps({
  stats: { type: Object, default: null },
})

// Brand chart series (UI-COMPONENTS §10): primary, secondary, accent, then muted.
const series = ['#2563EB', '#38BDF8', '#14B8A6', '#64748B']

const missing = (value) => value === null || value === undefined
const num = (value) => (missing(value) ? '—' : Number(value).toLocaleString())
const pct = (value) => (missing(value) ? '—' : `${value}%`)
const sum = (values) => values.reduce((total, value) => total + value, 0)
const statusLabel = (status) => status.charAt(0).toUpperCase() + status.slice(1).replaceAll('_', ' ')

// Students per program: the three largest, the rest grouped.
const livePrograms = computed(() => {
  const rows = props.stats?.enrollment_by_program ?? []
  const labels = rows.slice(0, 3).map((row) => row.name)
  const values = rows.slice(0, 3).map((row) => row.students)
  const rest = sum(rows.slice(3).map((row) => row.students))
  if (rest > 0) {
    labels.push('Other programs')
    values.push(rest)
  }
  return { labels, values }
})

// Letters grouped by band (A, B, C, then D and below), in scale order.
const liveGrades = computed(() => {
  const groups = new Map()
  for (const { grade, total } of props.stats?.grade_distribution ?? []) {
    const key = ['A', 'B', 'C'].includes(grade[0]) ? grade[0] : 'D and below'
    groups.set(key, (groups.get(key) ?? 0) + total)
  }
  return { labels: [...groups.keys()], values: [...groups.values()] }
})

const liveInternships = computed(() => (props.stats?.internships ?? []).filter((row) => row.total > 0))

const live = computed(() => {
  const o = props.stats?.overview ?? {}
  return {
    kpis: [o.students_enrolled, o.attendance_rate, o.pass_rate, o.average_gpa],
    programs: livePrograms.value,
    grades: liveGrades.value,
    internships: liveInternships.value,
  }
})

const sample = {
  kpis: [1180, 91, 88, 3.07],
  programs: { labels: ['Computer Science', 'Business Administration', 'Civil Engineering', 'Other programs'], values: [420, 310, 260, 190] },
  grades: { labels: ['A', 'B', 'C', 'D and below'], values: [214, 386, 275, 157] },
  internships: [
    { status: 'submitted', total: 14 },
    { status: 'under_review', total: 9 },
    { status: 'approved', total: 21 },
    { status: 'in_progress', total: 32 },
  ],
}

const hasLive = computed(() => sum(live.value.programs.values) > 0 || sum(live.value.grades.values) > 0 || live.value.internships.length > 0)
const data = computed(() => (hasLive.value ? live.value : sample))
const semester = computed(() => (hasLive.value ? (props.stats?.semester?.name ?? 'no semester yet') : 'sample semester'))

const kpis = computed(() => {
  const [enrolled, attendance, pass, gpa] = data.value.kpis
  return [
    { label: 'Students enrolled', value: num(enrolled), detail: 'This semester' },
    { label: 'Attendance rate', value: pct(attendance), detail: 'Held sessions' },
    { label: 'Pass rate', value: pct(pass), detail: 'Approved grades' },
    { label: 'Average GPA', value: missing(gpa) ? '—' : Number(gpa).toFixed(2), detail: 'Semester GPA' },
  ]
})
const maxInternships = computed(() => Math.max(1, ...data.value.internships.map((row) => row.total)))

const capabilities = [
  'Every figure for the whole university or one department',
  'Headline figures traced across the latest six semesters',
  'Each table as CSV, plus an executive PDF report',
]

const card = 'min-w-0 rounded-lg border border-border-default bg-surface p-5 shadow-sm dark:border-dark-border dark:bg-dark-surface'
</script>

<template>
  <section id="analytics" class="scroll-mt-20 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="analytics-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid gap-8 lg:grid-cols-[3fr_2fr] lg:items-end lg:gap-16">
        <Reveal>
          <p class="text-caption font-semibold tracking-widest text-primary uppercase dark:text-dark-primary">Analytics & reporting</p>
          <h2 id="analytics-title" class="font-display mt-3 text-h2 text-balance text-primary-dark dark:text-dark-ink">
            Turn Academic Information into Useful Insights
          </h2>
          <p class="mt-4 text-body text-muted dark:text-dark-muted">
            University Admins and Department Admins review enrollment, attendance, academic performance and administrative
            workload for any semester — computed live from the same records everyone works with.
          </p>
        </Reveal>
        <Reveal as="ul" :delay="100" class="space-y-3">
          <li v-for="item in capabilities" :key="item" class="flex gap-3 text-small text-ink dark:text-dark-ink">
            <CircleCheck class="mt-0.5 h-4 w-4 shrink-0 text-primary dark:text-dark-primary" aria-hidden="true" />
            {{ item }}
          </li>
        </Reveal>
      </div>

      <Reveal class="mt-12 rounded-xl border border-border-default bg-background p-4 sm:p-6 dark:border-dark-border dark:bg-dark-bg">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="font-display text-h4 text-primary-dark dark:text-dark-ink">Analytics · {{ semester }}</p>
          <BaseBadge v-if="hasLive" variant="success" size="md" dot>Live data</BaseBadge>
          <BaseBadge v-else variant="warning" size="md">Sample data</BaseBadge>
        </div>
        <p v-if="!hasLive" class="mt-2 text-small text-muted dark:text-dark-muted">
          This installation has no academic records to chart yet, so the preview shows sample figures — they do not describe a real university.
        </p>

        <dl class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
          <div v-for="kpi in kpis" :key="kpi.label" :class="card">
            <dt class="text-small text-muted dark:text-dark-muted">{{ kpi.label }}</dt>
            <dd class="mt-2 text-h3 font-bold tabular-nums text-ink dark:text-dark-ink">{{ kpi.value }}</dd>
            <dd class="text-caption text-muted dark:text-dark-muted">{{ kpi.detail }}</dd>
          </div>
        </dl>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
          <div :class="card">
            <h3 class="text-small font-semibold text-ink dark:text-dark-ink">Enrollment by program</h3>
            <p class="text-caption text-muted dark:text-dark-muted">Students enrolled this semester</p>
            <PieChart
              v-if="sum(data.programs.values)"
              class="mt-4"
              :labels="data.programs.labels"
              :values="data.programs.values"
              :colors="series"
              label="Enrollment by program"
              value-label="Students"
            />
            <EmptyState v-else title="No enrollments yet" description="Students appear here once they register for sections in the current semester." />
          </div>

          <div :class="card">
            <h3 class="text-small font-semibold text-ink dark:text-dark-ink">Grade distribution</h3>
            <p class="text-caption text-muted dark:text-dark-muted">Approved course grades, by band</p>
            <BarChart v-if="sum(data.grades.values)" class="mt-4" :labels="data.grades.labels" :values="data.grades.values" label="Grade distribution" value-label="Grades" />
            <EmptyState v-else title="No approved grades yet" description="The distribution appears once grade sheets are approved." />
          </div>

          <div :class="[card, 'lg:col-span-2']">
            <h3 class="text-small font-semibold text-ink dark:text-dark-ink">Internships by status</h3>
            <p class="text-caption text-muted dark:text-dark-muted">Current workload, all students</p>
            <ul v-if="data.internships.length" class="mt-4 grid gap-x-10 gap-y-3 md:grid-cols-2">
              <li v-for="row in data.internships" :key="row.status" class="grid grid-cols-[7rem_1fr_2.5rem] items-center gap-3">
                <span class="text-small text-muted dark:text-dark-muted">{{ statusLabel(row.status) }}</span>
                <span class="h-2 overflow-hidden rounded-pill bg-border-default dark:bg-dark-border" aria-hidden="true">
                  <span class="block h-full rounded-pill bg-chart-primary dark:bg-dark-chart-primary" :style="{ width: `${(row.total / maxInternships) * 100}%` }" />
                </span>
                <span class="text-right text-small font-semibold tabular-nums text-ink dark:text-dark-ink">{{ num(row.total) }}</span>
              </li>
            </ul>
            <EmptyState v-else title="No internships yet" description="Applications appear here as students submit them." />
          </div>
        </div>
      </Reveal>
    </div>
  </section>
</template>
