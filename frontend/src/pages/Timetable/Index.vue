<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'

const props = defineProps({
  entries: { type: Array, default: () => [] },
  days: { type: Object, default: () => ({}) },
  owner: { type: String, default: 'student' },
})

// Show Monday–Friday always, weekend columns only when something is scheduled.
const visibleDays = computed(() =>
  Object.entries(props.days)
    .map(([number, name]) => ({ number: Number(number), name }))
    .filter((day) => day.number <= 5 || props.entries.some((entry) => entry.day_of_week === day.number)),
)
const byDay = (day) => props.entries.filter((entry) => entry.day_of_week === day)
</script>

<template>
  <Head title="My timetable - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Timetable" title="My timetable" :description="owner === 'student' ? 'Weekly meetings of the sections you are enrolled in.' : 'Weekly meetings of the sections you teach.'" />

    <BaseCard v-if="!entries.length"><EmptyState title="Nothing scheduled" :description="owner === 'student' ? 'Enroll in sections from Course registration; their class times appear here.' : 'Sections assigned to you will appear here once they have class times.'" /></BaseCard>

    <div v-else class="grid gap-4 md:grid-cols-3 xl:grid-cols-5" :class="visibleDays.length > 5 ? 'xl:grid-cols-7' : ''">
      <section v-for="day in visibleDays" :key="day.number" :aria-labelledby="`day-${day.number}`" class="space-y-3">
        <h2 :id="`day-${day.number}`" class="text-small font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">{{ day.name }}</h2>
        <p v-if="!byDay(day.number).length" class="rounded-lg border border-dashed border-border-muted p-3 text-caption text-muted dark:border-dark-border dark:text-dark-muted">Free</p>
        <article v-for="entry in byDay(day.number)" :key="entry.id" class="glass-card rounded-lg border p-3">
          <p class="font-mono text-caption text-primary dark:text-dark-primary">{{ entry.start_time }}–{{ entry.end_time }}</p>
          <p class="mt-1 text-small font-semibold text-ink dark:text-dark-ink">{{ entry.course.code }} · {{ entry.section.code }}</p>
          <p class="text-caption text-muted dark:text-dark-muted">{{ entry.course.name }}</p>
          <p class="mt-2 text-caption text-muted dark:text-dark-muted">{{ entry.room.code }}<template v-if="entry.room.building"> · {{ entry.room.building }}</template></p>
          <p v-if="owner === 'student' && entry.lecturer" class="text-caption text-muted dark:text-dark-muted">{{ entry.lecturer }}</p>
        </article>
      </section>
    </div>
  </div>
</template>
