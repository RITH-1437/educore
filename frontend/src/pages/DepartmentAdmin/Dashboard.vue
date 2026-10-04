<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'

const props = defineProps({
  // Null when no department is assigned to the account (no unit data).
  dashboard: { type: Object, default: null },
  userName: { type: String, required: true },
})

// Requests of the department's students waiting to be processed (report 39);
// each count opens its queue filtered to that status.
const waiting = computed(() => {
  const w = props.dashboard?.waiting
  return !w ? [] : [
    { label: 'Requests to approve', value: w.document_requests_pending, detail: 'Document requests, pending', href: '/documents?filters[status]=pending' },
    { label: 'PDFs to generate', value: w.document_requests_approved, detail: 'Approved document requests', href: '/documents?filters[status]=approved' },
    { label: 'Applications to review', value: w.internships_submitted, detail: 'Internships submitted', href: '/internships?filters[status]=submitted' },
    { label: 'Decisions to make', value: w.internships_under_review, detail: 'Internships under review', href: '/internships?filters[status]=under_review' },
  ]
})

// Headline numbers on the analytics definitions; semester figures need a semester.
const numbers = computed(() => {
  const o = props.dashboard?.overview
  const semester = props.dashboard?.semester
  return !o ? [] : [
    { label: 'Active students', value: o.students_active, detail: 'In its programs', href: '/students?filters[status]=active' },
    { label: 'Active lecturers', value: o.lecturers_active, detail: 'In its department', href: '/lecturers?filters[is_active]=1' },
    { label: 'Sections', value: o.sections ?? '—', detail: semester ? 'Running this semester' : 'No semester yet', href: semester ? `/offerings?semester_id=${semester.id}` : '' },
    { label: 'Students enrolled', value: o.students_enrolled ?? '—', detail: semester ? 'Its students, this semester' : 'No semester yet', href: semester ? `/enrollments?semester_id=${semester.id}` : '' },
  ]
})

const delay = (step) => ({ animationDelay: `${step * 60}ms` })
</script>

<template>
  <Head title="Dashboard - EduCore" />
  <div class="space-y-8">
    <PageHeader
      eyebrow="Department dashboard"
      :title="`Welcome, ${userName}`"
      :description="dashboard ? [dashboard.department?.name, dashboard.semester?.name].filter(Boolean).join(' · ') : 'No department assigned'"
    />

    <BaseCard v-if="!dashboard">
      <EmptyState title="No department assigned" description="No department is assigned to your account yet, so unit data stays hidden. Ask a Super Admin to assign one." />
    </BaseCard>

    <template v-else>
      <section class="space-y-4" aria-labelledby="waiting-heading">
        <div>
          <h2 id="waiting-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Waiting for you</h2>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">Requests from your department’s students. Each count opens its queue.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard v-for="(metric, index) in waiting" :key="metric.label" v-bind="metric" class="motion-safe:animate-section-in" :style="delay(index)" />
        </div>
      </section>

      <section class="space-y-4" aria-labelledby="department-heading">
        <div>
          <h2 id="department-heading" class="text-h4 font-semibold text-ink dark:text-dark-ink">Your department</h2>
          <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ dashboard.semester ? `Semester figures are for ${dashboard.semester.name}.` : 'Semester figures appear once a semester exists.' }}</p>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <StatCard v-for="(metric, index) in numbers" :key="metric.label" v-bind="metric" class="motion-safe:animate-section-in" :style="delay(index + 4)" />
        </div>
      </section>
    </template>
  </div>
</template>
