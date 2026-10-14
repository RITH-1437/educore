<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { ArrowLeft, CalendarClock, ListChecks } from '@lucide/vue'
import { computed } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import IconButton from '../../components/IconButton.vue'

// One course of the public catalog (report 53).
const props = defineProps({
  course: { type: Object, required: true },
  semester: { type: Object, default: null },
})

const page = usePage()
const isStudent = computed(() => page.props.auth?.user?.role?.slug === 'student')
const hours = computed(() => [
  props.course.lecture_hours ? `${Number(props.course.lecture_hours)} h lecture` : null,
  props.course.lab_hours ? `${Number(props.course.lab_hours)} h lab` : null,
].filter(Boolean).join(' · '))
</script>

<script>
import DefaultLayout from '../../layouts/DefaultLayout.vue'
import PublicLayout from '../../layouts/PublicLayout.vue'

export default { layout: (h, page) => h(page.props.auth?.user ? DefaultLayout : PublicLayout, () => page) }
</script>

<template>
  <Head :title="`${course.code} ${course.name}`" />
  <div class="space-y-6">
    <header class="flex items-start justify-between gap-4">
      <div class="min-w-0">
        <p class="text-small text-muted dark:text-dark-muted">
          <span class="font-mono font-semibold text-primary dark:text-dark-primary">{{ course.code }}</span>
          <template v-if="course.department"> · {{ course.department.name }}</template>
        </p>
        <h1 class="mt-1 text-h1 font-display font-semibold text-ink dark:text-dark-ink">{{ course.name }}</h1>
        <div class="mt-3 flex flex-wrap gap-2">
          <BaseBadge variant="primary" size="sm">{{ Number(course.credits) }} credit{{ Number(course.credits) === 1 ? '' : 's' }}</BaseBadge>
          <BaseBadge v-if="course.course_level" variant="muted" size="sm">{{ course.course_level }}</BaseBadge>
          <BaseBadge v-if="hours" variant="muted" size="sm">{{ hours }}</BaseBadge>
          <BaseBadge :variant="course.offered ? 'success' : 'muted'" size="sm">{{ course.offered ? `Offered ${semester?.name ?? 'this semester'}` : 'Not offered this semester' }}</BaseBadge>
        </div>
      </div>
      <IconButton :icon="ArrowLeft" href="/catalog" size="md" label="Back to the catalog" />
    </header>

    <div class="grid gap-6 lg:grid-cols-3">
      <div class="space-y-6 lg:col-span-2">
        <BaseCard padding="lg">
          <h2 class="text-h4 font-semibold text-ink dark:text-dark-ink">About this course</h2>
          <p v-if="course.description" class="mt-3 whitespace-pre-line text-body text-ink dark:text-dark-ink">{{ course.description }}</p>
          <p v-else class="mt-3 text-small text-muted dark:text-dark-muted">No description has been published yet.</p>
        </BaseCard>

        <BaseCard padding="lg">
          <h2 class="flex items-center gap-2 text-h4 font-semibold text-ink dark:text-dark-ink">
            <CalendarClock class="h-5 w-5 text-primary dark:text-dark-primary" aria-hidden="true" />
            {{ semester ? `Sections in ${semester.name}` : 'Sections' }}
          </h2>
          <EmptyState v-if="!course.sections.length" class="mt-2" title="Not offered this semester" description="Sections and class times appear here when the course is scheduled." />
          <div v-else class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-small">
              <caption class="sr-only">Sections, lecturers and weekly class times</caption>
              <thead class="text-caption uppercase tracking-wide text-muted dark:text-dark-muted">
                <tr>
                  <th scope="col" class="py-2 pr-4 font-medium">Section</th>
                  <th scope="col" class="py-2 pr-4 font-medium">Lecturers</th>
                  <th scope="col" class="py-2 font-medium">Meets</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border-default dark:divide-dark-border">
                <tr v-for="section in course.sections" :key="section.code" class="align-top">
                  <td class="py-3 pr-4 font-semibold text-ink dark:text-dark-ink">{{ section.code }}</td>
                  <td class="py-3 pr-4 text-ink dark:text-dark-ink">{{ section.lecturers.join(', ') || '—' }}</td>
                  <td class="py-3 text-ink dark:text-dark-ink">
                    <span v-if="!section.meetings.length" class="text-muted dark:text-dark-muted">Times to be announced</span>
                    <span v-for="meeting in section.meetings" :key="meeting.day + meeting.start_time" class="block">
                      {{ meeting.day }} {{ meeting.start_time }}–{{ meeting.end_time }}<template v-if="meeting.room"> · {{ meeting.room }}</template>
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="isStudent && course.offered" class="mt-5 flex">
            <BaseButton href="/registration" variant="primary"><ListChecks class="h-4 w-4" aria-hidden="true" /> Go to course registration</BaseButton>
          </div>
        </BaseCard>
      </div>

      <div class="space-y-6">
        <BaseCard padding="lg">
          <h2 class="text-h4 font-semibold text-ink dark:text-dark-ink">Prerequisites</h2>
          <p v-if="!course.prerequisites.length" class="mt-2 text-small text-muted dark:text-dark-muted">None.</p>
          <ul v-else class="mt-3 space-y-2">
            <li v-for="prerequisite in course.prerequisites" :key="prerequisite.id" class="text-small">
              <Link :href="`/catalog/courses/${prerequisite.id}`" class="rounded-sm font-medium text-primary hover:underline focus-visible:outline-2 focus-visible:outline-primary dark:text-dark-primary">
                {{ prerequisite.code }} {{ prerequisite.name }}
              </Link>
              <span class="text-caption text-muted dark:text-dark-muted"> · {{ prerequisite.is_strict ? 'required' : 'recommended' }}</span>
            </li>
          </ul>
        </BaseCard>

        <BaseCard padding="lg">
          <h2 class="text-h4 font-semibold text-ink dark:text-dark-ink">In programs</h2>
          <p v-if="!course.programs.length" class="mt-2 text-small text-muted dark:text-dark-muted">Not part of a program yet.</p>
          <ul v-else class="mt-3 space-y-2">
            <li v-for="program in course.programs" :key="program.code" class="flex items-start justify-between gap-2 text-small text-ink dark:text-dark-ink">
              <span>{{ program.name }}</span>
              <BaseBadge :variant="program.is_required ? 'primary' : 'muted'" size="sm">{{ program.is_required ? 'Required' : 'Elective' }}</BaseBadge>
            </li>
          </ul>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
