<script setup>
import IconButton from '../../components/IconButton.vue'
import { ChartColumn, Download, FileSpreadsheet, FileText, PieChart as PieIcon } from '@lucide/vue'
import { exportUrl } from '../../utils/exports'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import StatusBreakdownCard from '../../components/analytics/StatusBreakdownCard.vue'
import BarChart from '../../components/charts/BarChart.vue'
import LineChart from '../../components/charts/LineChart.vue'
import PieChart from '../../components/charts/PieChart.vue'
import { money } from '../../utils/finance'

const props = defineProps({
  semesters: { type: Array, default: () => [] },
  semesterId: { type: Number, default: null },
  overview: { type: Object, default: null },
  enrollment: { type: Object, default: null },
  academic: { type: Object, default: null },
  // Null for a Department Admin with no department (nothing to show).
  administrative: { type: Object, default: null },
  // Managers' department filter options; empty for a Department Admin.
  departments: { type: Array, default: () => [] },
  // Whose figures these are (report 47): the department (or null = university),
  // whether it is fixed (Department Admin), and whether there is none to show.
  scope: { type: Object, default: () => ({ department: null, locked: false, unassigned: false }) },
  trends: { type: Array, default: () => [] },
})

// One filter row above the charts; changing it reloads every section.
const semester = ref(props.semesterId ?? '')
const department = ref(props.scope.locked ? '' : (props.scope.department?.id ?? ''))
const semesterOptions = computed(() => props.semesters.map((s) => ({ value: s.id, label: `${s.name}${s.status === 'open' ? ' (open)' : ''}` })))
const departmentOptions = computed(() => props.departments.map((d) => ({ value: d.id, label: `${d.name} (${d.code})` })))
const reload = () => router.get('/analytics', { semester_id: semester.value || undefined, department_id: department.value || undefined }, { preserveState: true, preserveScroll: true, replace: true })
const pick = () => reload()
const scoped = computed(() => Boolean(props.scope.department))
const scopeName = computed(() => props.scope.department?.name ?? 'the whole university')

const enrollmentChartType = ref('bar')
const gradeChartType = ref('pie')
const gpaChartType = ref('pie')

const departmentId = computed(() => props.scope.department?.id)
const pdfUrl = computed(() => exportUrl('/analytics/export/pdf', { semester_id: props.semesterId, department_id: departmentId.value }))
const csv = (table) => exportUrl('/analytics/export', { table, semester_id: props.semesterId, department_id: departmentId.value })
const pct = (v) => (v === null || v === undefined ? '—' : `${v}%`)
const o = computed(() => props.overview)
// Headline numbers, laid out like the dashboard overview: four per row, no
// icons, a short detail line on every card; counts that have a list link to
// it, filtered to the semester (and department) where the list supports it.
// For a department, people figures are its students and teaching figures its
// courses — the detail line says which.
// A manager's department filter carries over to the lists (a Department Admin's are scoped already).
const unitQuery = computed(() => (departmentId.value && !props.scope.locked ? `&filters[department_id]=${departmentId.value}` : ''))
const metrics = computed(() => (!o.value ? [] : [
  { label: 'Students enrolled', value: o.value.students_enrolled, detail: scoped.value ? `${o.value.enrollments} enrollments by its students` : `${o.value.enrollments} enrollments this semester`, href: `/enrollments?semester_id=${props.semesterId}` },
  { label: 'Active students', value: o.value.students_active, detail: scoped.value ? 'In its programs' : 'All programs', href: `/students?filters[status]=active${unitQuery.value}` },
  { label: 'Sections', value: o.value.sections, detail: scoped.value ? 'Of its courses, running this semester' : 'Running this semester', href: `/offerings?semester_id=${props.semesterId}` },
  { label: 'Active lecturers', value: o.value.lecturers_active, detail: scoped.value ? 'In the department' : 'Teaching staff', href: `/lecturers?filters[is_active]=1${unitQuery.value}` },
  { label: 'Attendance rate', value: pct(o.value.attendance_rate), detail: scoped.value ? 'Present + late, in its courses' : 'Present + late of counted sessions' },
  { label: 'Approved grades', value: o.value.grades_approved, detail: scoped.value ? 'Approved or final, in its courses' : 'Approved or finalized this semester' },
  { label: 'Pass rate', value: pct(o.value.pass_rate), detail: scoped.value ? 'Points above 0, in its courses' : 'Grades with points above 0' },
  { label: 'Average semester GPA', value: o.value.average_gpa === null ? '—' : o.value.average_gpa.toFixed(2), detail: scoped.value ? 'Its students with a semester GPA' : 'Students with a semester GPA' },
]))
const delay = (step) => ({ animationDelay: `${step * 60}ms` })
const a = computed(() => props.academic)
const hasGrades = computed(() => (a.value?.grade_distribution ?? []).some((g) => g.total > 0))
const hasGpa = computed(() => (a.value?.gpa_distribution ?? []).some((g) => g.total > 0))

// Trends: one measure at a time on one axis (never two scales on one chart).
const trendMetrics = [
  { key: 'enrollments', label: 'Enrollments', valueLabel: 'Enrollments' },
  { key: 'attendance_rate', label: 'Attendance', valueLabel: 'Attendance rate', suffix: '%', max: 100 },
  { key: 'pass_rate', label: 'Pass rate', valueLabel: 'Pass rate', suffix: '%', max: 100 },
  { key: 'average_gpa', label: 'Average GPA', valueLabel: 'Average semester GPA', max: 4, decimals: 2 },
]
const trendKey = ref('enrollments')
const trend = computed(() => trendMetrics.find((m) => m.key === trendKey.value))
const hasTrends = computed(() => props.trends.length > 1)
</script>

<template>
  <Head title="Analytics - EduCore" />
  <div class="space-y-8">
    <PageHeader
      :eyebrow="scope.locked && scope.department ? scope.department.name : 'Reports'"
      title="Analytics"
      :description="scope.unassigned
        ? 'Department analytics follow the department you are assigned to.'
        : `Enrollment, academic performance and administrative workload for ${scopeName}. Academic figures use approved grades only.`"
    >
      <template v-if="overview" #actions>
        <a
          :href="pdfUrl"
          class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-border-default bg-surface px-4 py-2 text-button font-medium text-ink transition duration-150 ease-out hover:bg-background focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink dark:hover:bg-dark-surface-2"
        >
          <FileText class="size-4 text-primary" />
          <span>Export PDF</span>
        </a>
      </template>
    </PageHeader>

    <BaseCard v-if="scope.unassigned">
      <EmptyState title="No department assigned" description="Ask a Super Admin to assign your department; its analytics then appear here." />
    </BaseCard>

    <template v-else>
    <div class="grid max-w-2xl gap-4 sm:grid-cols-2">
      <BaseSelect v-model="semester" :options="semesterOptions" label="Semester" placeholder="No semesters yet" @update:model-value="pick" />
      <BaseSelect v-if="!scope.locked" v-model="department" :options="departmentOptions" label="Department" placeholder="All departments" @update:model-value="pick" />
    </div>

    <BaseCard v-if="!overview">
      <EmptyState title="No semester to report on" description="Create an academic year and a semester to see enrollment and academic analytics." />
    </BaseCard>

    <template v-else>
      <!-- KPI rows: headline numbers are tiles, not charts (same layout as the dashboard overview). -->
      <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Headline numbers">
        <StatCard v-for="(metric, index) in metrics" :key="metric.label" v-bind="metric" class="motion-safe:animate-section-in" :style="delay(index)" />
      </section>

      <div class="grid gap-6 xl:grid-cols-2">
        <BaseCard title="Enrollment by program" padding="lg">
          <template #actions>
            <div class="flex items-center gap-1">
              <IconButton
                :icon="enrollmentChartType === 'pie' ? ChartColumn : PieIcon"
                size="sm"
                :label="enrollmentChartType === 'pie' ? 'Switch to bar chart' : 'Switch to donut chart'"
                @click="enrollmentChartType = enrollmentChartType === 'pie' ? 'bar' : 'pie'"
              />
              <IconButton :icon="Download" :href="csv('enrollment_by_program')" native size="sm" label="Download enrollment by program as CSV" />
            </div>
          </template>
          <EmptyState v-if="!enrollment.by_program.length" title="No enrollments" description="Students' registrations in this semester appear here." />
          <PieChart
            v-else-if="enrollmentChartType === 'pie'"
            label="Enrollments by program"
            value-label="Enrollments"
            :labels="enrollment.by_program.map((p) => p.code)"
            :details="enrollment.by_program.map((p) => `${p.name} (${p.students} students)`)"
            :values="enrollment.by_program.map((p) => p.enrollments)"
          />
          <BarChart
            v-else
            horizontal
            label="Enrollments by program"
            value-label="Enrollments"
            :labels="enrollment.by_program.map((p) => p.code)"
            :details="enrollment.by_program.map((p) => `${p.name} (${p.students} students)`)"
            :values="enrollment.by_program.map((p) => p.enrollments)"
          />
        </BaseCard>

        <BaseCard title="Attendance by course" padding="lg">
          <template #actions><IconButton :icon="Download" :href="csv('attendance_by_course')" native size="sm" label="Download attendance by course as CSV" /></template>
          <template #description>Lowest first — courses under 75% need attention.</template>
          <EmptyState v-if="!academic.attendance_by_course.length" title="No attendance yet" description="Rates appear once sessions are recorded." />
          <BarChart v-else horizontal label="Attendance rate by course" value-label="Attendance" suffix="%" :max="100" :labels="academic.attendance_by_course.map((c) => c.code)" :details="academic.attendance_by_course.map((c) => c.name)" :values="academic.attendance_by_course.map((c) => c.rate)" />
        </BaseCard>

        <BaseCard title="Grade distribution" padding="lg">
          <template #actions>
            <div class="flex items-center gap-1">
              <IconButton
                :icon="gradeChartType === 'pie' ? ChartColumn : PieIcon"
                size="sm"
                :label="gradeChartType === 'pie' ? 'Switch to bar chart' : 'Switch to donut chart'"
                @click="gradeChartType = gradeChartType === 'pie' ? 'bar' : 'pie'"
              />
              <IconButton :icon="Download" :href="csv('grade_distribution')" native size="sm" label="Download grade distribution as CSV" />
            </div>
          </template>
          <template #description>Approved course grades on the active scale.</template>
          <EmptyState v-if="!hasGrades" title="No approved grades" description="The distribution appears once section grades are approved." />
          <PieChart
            v-else-if="gradeChartType === 'pie'"
            label="Grades by letter"
            value-label="Students"
            :labels="academic.grade_distribution.map((g) => g.grade)"
            :values="academic.grade_distribution.map((g) => g.total)"
          />
          <BarChart
            v-else
            label="Grades by letter"
            value-label="Students"
            :labels="academic.grade_distribution.map((g) => g.grade)"
            :values="academic.grade_distribution.map((g) => g.total)"
          />
        </BaseCard>

        <BaseCard title="Semester GPA distribution" padding="lg">
          <template #actions>
            <div class="flex items-center gap-1">
              <IconButton
                :icon="gpaChartType === 'pie' ? ChartColumn : PieIcon"
                size="sm"
                :label="gpaChartType === 'pie' ? 'Switch to bar chart' : 'Switch to donut chart'"
                @click="gpaChartType = gpaChartType === 'pie' ? 'bar' : 'pie'"
              />
              <IconButton :icon="Download" :href="csv('gpa_distribution')" native size="sm" label="Download semester GPA distribution as CSV" />
            </div>
          </template>
          <EmptyState v-if="!hasGpa" title="No GPAs yet" description="Semester GPAs are computed when grades are approved." />
          <PieChart
            v-else-if="gpaChartType === 'pie'"
            label="Students by semester GPA band"
            value-label="Students"
            :labels="academic.gpa_distribution.map((g) => g.band)"
            :values="academic.gpa_distribution.map((g) => g.total)"
          />
          <BarChart
            v-else
            label="Students by semester GPA band"
            value-label="Students"
            :labels="academic.gpa_distribution.map((g) => g.band)"
            :values="academic.gpa_distribution.map((g) => g.total)"
          />
        </BaseCard>
      </div>

      <BaseCard v-if="academic.courses.length" title="Results by course" padding="lg">
        <template #actions><IconButton :icon="Download" :href="csv('course_results')" native label="Download results by course as CSV" /></template>
        <div class="-mx-2 overflow-x-auto">
          <table class="min-w-full text-small">
            <caption class="sr-only">Results by course</caption>
            <thead>
              <tr class="border-b border-border-default text-left text-caption font-semibold uppercase tracking-wider text-muted dark:border-dark-border dark:text-dark-muted">
                <th scope="col" class="px-2 py-2">Course</th>
                <th scope="col" class="px-2 py-2 text-right">Graded</th>
                <th scope="col" class="px-2 py-2 text-right">Pass rate</th>
                <th scope="col" class="px-2 py-2 text-right">Average total</th>
                <th scope="col" class="px-2 py-2 text-right">Average points</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border-default dark:divide-dark-border">
              <tr v-for="c in academic.courses" :key="c.code">
                <td class="px-2 py-2 text-ink dark:text-dark-ink">{{ c.code }} · {{ c.name }}</td>
                <td class="px-2 py-2 text-right tabular-nums">{{ c.graded }}</td>
                <td class="px-2 py-2 text-right tabular-nums" :class="c.pass_rate !== null && c.pass_rate < 75 ? 'font-semibold text-error' : ''">{{ pct(c.pass_rate) }}</td>
                <td class="px-2 py-2 text-right tabular-nums">{{ c.average_total ?? '—' }}</td>
                <td class="px-2 py-2 text-right tabular-nums">{{ c.average_point?.toFixed(2) ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </template>

    <!-- Trends: the headline figures across the latest semesters, one measure at a time. -->
    <BaseCard v-if="hasTrends" title="Trends across semesters" padding="lg">
      <template #description>The latest {{ trends.length }} semesters, oldest first, on the same definitions as the numbers above.</template>
      <template #actions><IconButton :icon="Download" :href="csv('trends')" native size="sm" label="Download trends as CSV" /></template>
      <div class="mb-4 flex flex-wrap items-center gap-1.5" role="tablist" aria-label="Trend measure">
        <button
          v-for="m in trendMetrics"
          :key="m.key"
          type="button"
          role="tab"
          :aria-selected="trendKey === m.key"
          class="inline-flex min-h-8 items-center rounded-pill px-3 text-caption font-medium transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-primary"
          :class="trendKey === m.key
            ? 'bg-primary text-white shadow-xs dark:bg-dark-primary dark:text-dark-bg'
            : 'border border-border-default bg-surface text-muted hover:bg-muted-light/60 hover:text-ink dark:border-dark-border dark:bg-dark-surface dark:text-dark-muted dark:hover:bg-dark-muted/20 dark:hover:text-dark-ink'"
          @click="trendKey = m.key"
        >
          {{ m.label }}
        </button>
      </div>
      <LineChart
        :label="`${trend.valueLabel} by semester`"
        :value-label="trend.valueLabel"
        :suffix="trend.suffix ?? ''"
        :max="trend.max ?? null"
        :decimals="trend.decimals ?? null"
        :labels="trends.map((t) => t.semester)"
        :values="trends.map((t) => t[trend.key])"
      />
    </BaseCard>

    <!-- Administrative workload: point in time, not filtered by semester. A
         department's covers its students' requests; finance is university-wide only. -->
    <section class="space-y-4" aria-labelledby="workload-heading">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="workload-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Current workload</h2>
        <div class="flex flex-wrap gap-2">
          <IconButton v-if="administrative.finance" :icon="Download" :href="csv('finance')" native size="md" label="Download finance as CSV" />
          <IconButton :icon="FileSpreadsheet" :href="csv('workload')" native size="md" label="Download workload as CSV" />
        </div>
      </div>

      <div v-for="row in administrative.finance ?? []" :key="row.currency" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard :label="`Invoiced (${row.currency})`" :value="money(row.invoiced, row.currency)" tone="muted" detail="Excluding cancelled invoices" />
        <StatCard :label="`Collected (${row.currency})`" :value="money(row.collected, row.currency)" tone="success" :detail="`${pct(row.collection_rate)} of invoiced`" />
        <StatCard :label="`Outstanding (${row.currency})`" :value="money(row.outstanding, row.currency)" tone="warning" detail="Invoiced minus collected" />
        <StatCard :label="`Overdue (${row.currency})`" :value="money(row.overdue, row.currency)" :tone="row.overdue > 0 ? 'warning' : 'muted'" :detail="`${row.overdue_count} invoice${row.overdue_count === 1 ? '' : 's'}`" />
      </div>

      <div class="grid gap-6" :class="administrative.invoices ? 'lg:grid-cols-3' : 'lg:grid-cols-2'">
        <StatusBreakdownCard title="Document requests" :rows="administrative.documents" href="/documents" value-label="Requests" />
        <StatusBreakdownCard title="Internships" :rows="administrative.internships" href="/internships" value-label="Internships" />
        <StatusBreakdownCard v-if="administrative.invoices" title="Invoices" :rows="administrative.invoices" href="/invoices" value-label="Invoices" />
      </div>
    </section>
    </template>
  </div>
</template>
