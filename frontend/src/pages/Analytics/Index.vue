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
import PieChart from '../../components/charts/PieChart.vue'
import { money } from '../../utils/finance'

const props = defineProps({
  semesters: { type: Array, default: () => [] },
  semesterId: { type: Number, default: null },
  overview: { type: Object, default: null },
  enrollment: { type: Object, default: null },
  academic: { type: Object, default: null },
  administrative: { type: Object, required: true },
})

// One filter row above the charts; changing it reloads every section.
const semester = ref(props.semesterId ?? '')
const semesterOptions = computed(() => props.semesters.map((s) => ({ value: s.id, label: `${s.name}${s.status === 'open' ? ' (open)' : ''}` })))
const pick = (value) => router.get('/analytics', { semester_id: value || undefined }, { preserveState: true, preserveScroll: true, replace: true })

const enrollmentChartType = ref('bar')
const gradeChartType = ref('pie')
const gpaChartType = ref('pie')

const pdfUrl = computed(() => exportUrl('/analytics/export/pdf', { semester_id: props.semesterId }))
const csv = (table) => exportUrl('/analytics/export', { table, semester_id: props.semesterId })
const pct = (v) => (v === null || v === undefined ? '—' : `${v}%`)
const o = computed(() => props.overview)
// Headline numbers, laid out like the dashboard overview: four per row, no
// icons, a short detail line on every card; counts that have a list link to
// it, filtered to the semester where the list supports it.
const metrics = computed(() => (!o.value ? [] : [
  { label: 'Students enrolled', value: o.value.students_enrolled, detail: `${o.value.enrollments} enrollments this semester`, href: `/enrollments?semester_id=${props.semesterId}` },
  { label: 'Active students', value: o.value.students_active, detail: 'All programs', href: '/students?filters[status]=active' },
  { label: 'Sections', value: o.value.sections, detail: 'Running this semester', href: `/offerings?semester_id=${props.semesterId}` },
  { label: 'Active lecturers', value: o.value.lecturers_active, detail: 'Teaching staff', href: '/lecturers?filters[is_active]=1' },
  { label: 'Attendance rate', value: pct(o.value.attendance_rate), detail: 'Present + late of counted sessions' },
  { label: 'Approved grades', value: o.value.grades_approved, detail: 'Approved or finalized this semester' },
  { label: 'Pass rate', value: pct(o.value.pass_rate), detail: 'Grades with points above 0' },
  { label: 'Average semester GPA', value: o.value.average_gpa === null ? '—' : o.value.average_gpa.toFixed(2), detail: 'Students with a semester GPA' },
]))
const delay = (step) => ({ animationDelay: `${step * 60}ms` })
const a = computed(() => props.academic)
const hasGrades = computed(() => (a.value?.grade_distribution ?? []).some((g) => g.total > 0))
const hasGpa = computed(() => (a.value?.gpa_distribution ?? []).some((g) => g.total > 0))
</script>

<template>
  <Head title="Analytics - EduCore" />
  <div class="space-y-8">
    <PageHeader eyebrow="Reports" title="Analytics" description="Enrollment, academic performance and administrative workload. Academic figures use approved grades only.">
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

    <div class="max-w-sm">
      <BaseSelect v-model="semester" :options="semesterOptions" label="Semester" placeholder="No semesters yet" @update:model-value="pick" />
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

    <!-- Administrative workload: point in time, not filtered by semester. -->
    <section class="space-y-4" aria-labelledby="workload-heading">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 id="workload-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Current workload</h2>
        <div class="flex flex-wrap gap-2">
          <IconButton :icon="Download" :href="csv('finance')" native size="md" label="Download finance as CSV" />
          <IconButton :icon="FileSpreadsheet" :href="csv('workload')" native size="md" label="Download workload as CSV" />
        </div>
      </div>

      <div v-for="row in administrative.finance" :key="row.currency" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard :label="`Invoiced (${row.currency})`" :value="money(row.invoiced, row.currency)" tone="muted" detail="Excluding cancelled invoices" />
        <StatCard :label="`Collected (${row.currency})`" :value="money(row.collected, row.currency)" tone="success" :detail="`${pct(row.collection_rate)} of invoiced`" />
        <StatCard :label="`Outstanding (${row.currency})`" :value="money(row.outstanding, row.currency)" tone="warning" detail="Invoiced minus collected" />
        <StatCard :label="`Overdue (${row.currency})`" :value="money(row.overdue, row.currency)" :tone="row.overdue > 0 ? 'warning' : 'muted'" :detail="`${row.overdue_count} invoice${row.overdue_count === 1 ? '' : 's'}`" />
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <StatusBreakdownCard title="Document requests" :rows="administrative.documents" href="/documents" value-label="Requests" />
        <StatusBreakdownCard title="Internships" :rows="administrative.internships" href="/internships" value-label="Internships" />
        <StatusBreakdownCard title="Invoices" :rows="administrative.invoices" href="/invoices" value-label="Invoices" />
      </div>
    </section>
  </div>
</template>
