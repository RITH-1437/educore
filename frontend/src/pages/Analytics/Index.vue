<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Award, BookOpenCheck, CircleCheck, GraduationCap, UserCheck, Users } from '@lucide/vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import BarChart from '../../components/charts/BarChart.vue'
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

const pct = (v) => (v === null || v === undefined ? '—' : `${v}%`)
const o = computed(() => props.overview)
const a = computed(() => props.academic)
const hasGrades = computed(() => (a.value?.grade_distribution ?? []).some((g) => g.total > 0))
const hasGpa = computed(() => (a.value?.gpa_distribution ?? []).some((g) => g.total > 0))
const label = (s) => s.replaceAll('_', ' ').replace(/^./, (c) => c.toUpperCase())
</script>

<template>
  <Head title="Analytics - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Reports" title="Analytics" description="Enrollment, academic performance and administrative workload. Academic figures use approved grades only." />

    <div class="max-w-sm">
      <BaseSelect v-model="semester" :options="semesterOptions" label="Semester" placeholder="No semesters yet" @update:model-value="pick" />
    </div>

    <BaseCard v-if="!overview">
      <EmptyState title="No semester to report on" description="Create an academic year and a semester to see enrollment and academic analytics." />
    </BaseCard>

    <template v-else>
      <!-- KPI row: headline numbers are tiles, not charts. -->
      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Headline numbers">
        <StatCard label="Students enrolled" :value="o.students_enrolled" :icon="GraduationCap" :detail="`${o.enrollments} enrollments · ${o.students_active} active students`" />
        <StatCard label="Sections" :value="o.sections" :icon="BookOpenCheck" tone="secondary" :detail="`${o.lecturers_active} active lecturers`" />
        <StatCard label="Attendance rate" :value="pct(o.attendance_rate)" :icon="UserCheck" :tone="o.attendance_rate !== null && o.attendance_rate < 75 ? 'warning' : 'success'" detail="Present + late of counted sessions" />
        <StatCard label="Approved grades" :value="o.grades_approved" :icon="CircleCheck" tone="muted" />
        <StatCard label="Pass rate" :value="pct(o.pass_rate)" :icon="Award" :tone="o.pass_rate !== null && o.pass_rate < 75 ? 'warning' : 'success'" detail="Grades with points above 0" />
        <StatCard label="Average semester GPA" :value="o.average_gpa === null ? '—' : o.average_gpa.toFixed(2)" :icon="Users" tone="secondary" />
      </section>

      <div class="grid gap-6 xl:grid-cols-2">
        <BaseCard title="Enrollment by program" padding="lg">
          <EmptyState v-if="!enrollment.by_program.length" title="No enrollments" description="Students' registrations in this semester appear here." />
          <BarChart v-else horizontal label="Enrollments by program" value-label="Enrollments" :labels="enrollment.by_program.map((p) => p.code)" :details="enrollment.by_program.map((p) => `${p.name} (${p.students} students)`)" :values="enrollment.by_program.map((p) => p.enrollments)" />
        </BaseCard>

        <BaseCard title="Attendance by course" padding="lg">
          <template #description>Lowest first — courses under 75% need attention.</template>
          <EmptyState v-if="!academic.attendance_by_course.length" title="No attendance yet" description="Rates appear once sessions are recorded." />
          <BarChart v-else horizontal label="Attendance rate by course" value-label="Attendance" suffix="%" :max="100" :labels="academic.attendance_by_course.map((c) => c.code)" :details="academic.attendance_by_course.map((c) => c.name)" :values="academic.attendance_by_course.map((c) => c.rate)" />
        </BaseCard>

        <BaseCard title="Grade distribution" padding="lg">
          <template #description>Approved course grades on the active scale.</template>
          <EmptyState v-if="!hasGrades" title="No approved grades" description="The distribution appears once section grades are approved." />
          <BarChart v-else label="Grades by letter" value-label="Students" :labels="academic.grade_distribution.map((g) => g.grade)" :values="academic.grade_distribution.map((g) => g.total)" />
        </BaseCard>

        <BaseCard title="Semester GPA distribution" padding="lg">
          <EmptyState v-if="!hasGpa" title="No GPAs yet" description="Semester GPAs are computed when grades are approved." />
          <BarChart v-else label="Students by semester GPA band" value-label="Students" :labels="academic.gpa_distribution.map((g) => g.band)" :values="academic.gpa_distribution.map((g) => g.total)" />
        </BaseCard>
      </div>

      <BaseCard v-if="academic.courses.length" title="Results by course" padding="lg">
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
      <h2 id="workload-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Current workload</h2>

      <div v-for="row in administrative.finance" :key="row.currency" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard :label="`Invoiced (${row.currency})`" :value="money(row.invoiced, row.currency)" tone="muted" detail="Excluding cancelled invoices" />
        <StatCard :label="`Collected (${row.currency})`" :value="money(row.collected, row.currency)" tone="success" :detail="`${pct(row.collection_rate)} of invoiced`" />
        <StatCard :label="`Outstanding (${row.currency})`" :value="money(row.outstanding, row.currency)" tone="warning" />
        <StatCard :label="`Overdue (${row.currency})`" :value="money(row.overdue, row.currency)" :tone="row.overdue > 0 ? 'warning' : 'muted'" :detail="`${row.overdue_count} invoice${row.overdue_count === 1 ? '' : 's'}`" />
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <BaseCard v-for="block in [{ title: 'Document requests', rows: administrative.documents, href: '/documents' }, { title: 'Internships', rows: administrative.internships, href: '/internships' }, { title: 'Invoices', rows: administrative.invoices, href: '/invoices' }]" :key="block.title" :title="block.title">
          <ul class="divide-y divide-border-default dark:divide-dark-border">
            <li v-for="row in block.rows" :key="row.status" class="flex items-center justify-between py-2">
              <StatusBadge :status="row.status" :label="label(row.status)" />
              <span class="text-small font-semibold tabular-nums text-ink dark:text-dark-ink">{{ row.total }}</span>
            </li>
          </ul>
        </BaseCard>
      </div>
    </section>
  </div>
</template>
