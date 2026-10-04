<script setup>
import { computed } from 'vue'
import BaseBadge from '../BaseBadge.vue'
import EmptyState from '../EmptyState.vue'
import PieChart from '../charts/PieChart.vue'
import Reveal from './Reveal.vue'
import SectionHeading from './SectionHeading.vue'

// Live, aggregate-only figures (LandingStatsService) on the definitions of the
// analytics page (module 9.23). An empty database shows empty states, never
// invented numbers.
const props = defineProps({
  stats: { type: Object, default: null },
})

// Brand chart series (UI-COMPONENTS §10): primary, secondary, accent, then muted.
// Four slices at most, so the donuts never turn into a rainbow.
const series = ['#2563EB', '#38BDF8', '#14B8A6', '#64748B']

const missing = (value) => value === null || value === undefined
const num = (value) => (missing(value) ? '—' : Number(value).toLocaleString())
const pct = (value) => (missing(value) ? '—' : `${value}%`)
const sum = (values) => values.reduce((total, value) => total + value, 0)

const semester = computed(() => props.stats?.semester?.name ?? null)
const overview = computed(() => props.stats?.overview ?? {})

const kpis = computed(() => [
  { label: 'Students enrolled', value: num(overview.value.students_enrolled), detail: 'This semester' },
  { label: 'Attendance rate', value: pct(overview.value.attendance_rate), detail: 'Held sessions' },
  { label: 'Pass rate', value: pct(overview.value.pass_rate), detail: 'Approved grades' },
  { label: 'Average GPA', value: missing(overview.value.average_gpa) ? '—' : Number(overview.value.average_gpa).toFixed(2), detail: 'Semester GPA' },
])

// Students per program: the three largest, the rest grouped.
const programs = computed(() => {
  const rows = props.stats?.enrollment_by_program ?? []
  const labels = rows.slice(0, 3).map((row) => row.name)
  const values = rows.slice(0, 3).map((row) => row.students)
  const rest = sum(rows.slice(3).map((row) => row.students))
  if (rest > 0) {
    labels.push('Other programs')
    values.push(rest)
  }
  return { labels, values, total: sum(values) }
})

// Letters grouped by band (A, B, C, then D and below), in scale order.
const grades = computed(() => {
  const groups = new Map()
  for (const { grade, total } of props.stats?.grade_distribution ?? []) {
    const key = ['A', 'B', 'C'].includes(grade[0]) ? `${grade[0]} grades` : 'D and below'
    groups.set(key, (groups.get(key) ?? 0) + total)
  }
  const values = [...groups.values()]
  return { labels: [...groups.keys()], values, total: sum(values) }
})

const courses = computed(() => props.stats?.courses ?? [])

const internships = computed(() => (props.stats?.internships ?? []).filter((row) => row.total > 0))
const maxInternships = computed(() => Math.max(1, ...internships.value.map((row) => row.total)))
const statusLabel = (status) => status.charAt(0).toUpperCase() + status.slice(1).replaceAll('_', ' ')

const card = 'rounded-lg border border-border-default bg-surface p-5 shadow-sm dark:border-dark-border dark:bg-dark-surface'
</script>

<template>
  <section id="analytics" class="scroll-mt-24 bg-surface py-20 sm:py-24 dark:bg-dark-surface" aria-labelledby="analytics-title">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <SectionHeading
        id="analytics-title"
        eyebrow="Analytics"
        title="Decisions based on the real records"
        description="Enrollment, attendance, academic performance and internships, computed live from the records — the same figures managers see on the analytics page, where they export them as CSV or PDF."
      />

      <Reveal class="mt-12 rounded-xl border border-border-default bg-background p-4 sm:p-6 dark:border-dark-border dark:bg-dark-bg">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="font-display text-h4 text-primary-dark dark:text-dark-ink">
            Analytics · {{ semester ?? 'no semester yet' }}
          </p>
          <BaseBadge variant="success" size="md" dot>Live data</BaseBadge>
        </div>

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
            <p class="text-caption text-muted dark:text-dark-muted">Students enrolled this semester, by current program</p>
            <PieChart v-if="programs.total" class="mt-4" :labels="programs.labels" :values="programs.values" :colors="series" label="Enrollment by program" value-label="Students" />
            <EmptyState v-else title="No enrollments yet" description="Students appear here once they register for sections in the current semester." />
          </div>
          <div :class="card">
            <h3 class="text-small font-semibold text-ink dark:text-dark-ink">Grade distribution</h3>
            <p class="text-caption text-muted dark:text-dark-muted">Approved course grades this semester, by band</p>
            <PieChart v-if="grades.total" class="mt-4" :labels="grades.labels" :values="grades.values" :colors="series" label="Grade distribution" value-label="Grades" />
            <EmptyState v-else title="No approved grades yet" description="The distribution appears once grade sheets are approved." />
          </div>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
          <div :class="card">
            <h3 class="text-small font-semibold text-ink dark:text-dark-ink">Results per course</h3>
            <p class="text-caption text-muted dark:text-dark-muted">Courses with at least five approved grades</p>
            <div v-if="courses.length" class="-mx-2 mt-3 overflow-x-auto">
              <table class="min-w-full text-small">
                <caption class="sr-only">Results per course</caption>
                <thead>
                  <tr class="border-b border-border-default text-left text-muted dark:border-dark-border dark:text-dark-muted">
                    <th scope="col" class="px-2 py-2 font-semibold">Course</th>
                    <th scope="col" class="px-2 py-2 text-right font-semibold">Graded</th>
                    <th scope="col" class="px-2 py-2 text-right font-semibold">Pass rate</th>
                    <th scope="col" class="px-2 py-2 text-right font-semibold">Average</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border-default dark:divide-dark-border">
                  <tr v-for="row in courses" :key="row.code">
                    <th scope="row" class="px-2 py-2 text-left font-normal text-ink dark:text-dark-ink">{{ row.code }} {{ row.name }}</th>
                    <td class="px-2 py-2 text-right tabular-nums text-ink dark:text-dark-ink">{{ num(row.graded) }}</td>
                    <td class="px-2 py-2 text-right tabular-nums text-ink dark:text-dark-ink">{{ pct(row.pass_rate) }}</td>
                    <td class="px-2 py-2 text-right tabular-nums text-ink dark:text-dark-ink">{{ missing(row.average_total) ? '—' : row.average_total }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <EmptyState v-else title="No course results yet" description="Courses appear once they have at least five approved grades." />
          </div>

          <div :class="card">
            <h3 class="text-small font-semibold text-ink dark:text-dark-ink">Internships by status</h3>
            <p class="text-caption text-muted dark:text-dark-muted">Current workload, all students</p>
            <ul v-if="internships.length" class="mt-4 space-y-3">
              <li v-for="row in internships" :key="row.status" class="grid grid-cols-[7rem_1fr_2.5rem] items-center gap-3">
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
