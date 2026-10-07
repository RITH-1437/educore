<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'

const props = defineProps({
  dashboard: { type: Object, required: true },
  userName: { type: String, required: true },
})

// Institution-wide action items waiting for processing; each count opens its queue.
const waiting = computed(() => {
  const w = props.dashboard?.waiting
  return !w ? [] : [
    { label: 'Requests to approve', value: w.document_requests_pending, detail: 'Document requests, pending', href: '/documents?filters[status]=pending' },
    { label: 'PDFs to generate', value: w.document_requests_approved, detail: 'Approved document requests', href: '/documents?filters[status]=approved' },
    { label: 'Applications to review', value: w.internships_submitted, detail: 'Internships submitted', href: '/internships?filters[status]=submitted' },
    { label: 'Decisions to make', value: w.internships_under_review, detail: 'Internships under review', href: '/internships?filters[status]=under_review' },
    { label: 'Overdue invoices', value: w.invoices_overdue, detail: 'Past due date', href: '/invoices?filters[status]=overdue' },
  ]
})

// Headline numbers across the institution; semester figures need an active semester.
const numbers = computed(() => {
  const o = props.dashboard?.overview
  const semester = props.dashboard?.semester
  return !o ? [] : [
    { label: 'Active students', value: o.students_active, detail: 'Across all programs', href: '/students?filters[status]=active' },
    { label: 'Active lecturers', value: o.lecturers_active, detail: 'Across all departments', href: '/lecturers?filters[is_active]=1' },
    { label: 'Sections running', value: o.sections ?? '—', detail: semester ? 'Running this semester' : 'No semester yet', href: semester ? `/offerings?semester_id=${semester.id}` : '' },
    { label: 'Students enrolled', value: o.students_enrolled ?? '—', detail: semester ? 'Enrolled this semester' : 'No semester yet', href: semester ? `/enrollments?semester_id=${semester.id}` : '' },
    { label: 'Attendance rate', value: o.attendance_rate !== null ? `${o.attendance_rate}%` : '—', detail: semester ? 'Current semester average' : 'No semester yet', href: '/analytics' },
    { label: 'Pass rate', value: o.pass_rate !== null ? `${o.pass_rate}%` : '—', detail: semester ? 'Approved course grades' : 'No semester yet', href: '/analytics' },
  ]
})

const delay = (step) => ({ animationDelay: `${step * 60}ms` })
</script>

<template>
  <Head title="Dashboard - EduCore" />
  <div class="space-y-8">
    <PageHeader
      eyebrow="University dashboard"
      :title="userName"
      :description="dashboard.semester ? `Current academic period: ${dashboard.semester.name}` : 'No active semester'"
    />

    <section class="space-y-4" aria-labelledby="waiting-heading">
      <div>
        <h2 id="waiting-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Waiting for action</h2>
        <p class="mt-1 text-small text-muted dark:text-dark-muted">Institution-wide requests and finance items requiring attention. Each count opens its queue.</p>
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <StatCard v-for="(metric, index) in waiting" :key="metric.label" v-bind="metric" class="motion-safe:animate-section-in" :style="delay(index)" />
      </div>
    </section>

    <section class="space-y-4" aria-labelledby="institution-heading">
      <div>
        <h2 id="institution-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Academic overview</h2>
        <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ dashboard.semester ? `Key academic figures for ${dashboard.semester.name}.` : 'Semester figures appear once an active semester exists.' }}</p>
      </div>
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <StatCard v-for="(metric, index) in numbers" :key="metric.label" v-bind="metric" class="motion-safe:animate-section-in" :style="delay(index + 5)" />
      </div>
    </section>
  </div>
</template>
