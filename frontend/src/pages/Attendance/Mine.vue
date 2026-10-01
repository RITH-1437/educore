<script setup>
import { Head } from '@inertiajs/vue3'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'

defineProps({
  summary: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="My attendance - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Attendance" title="My attendance" description="Present and late count as attended; excused and cancelled classes are not counted." />

    <BaseCard v-if="!summary.length"><EmptyState title="No attendance yet" description="Your attendance appears here once your lecturers start taking it." /></BaseCard>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <BaseCard v-for="row in summary" :key="row.enrollment_id">
        <p class="text-small font-semibold text-ink dark:text-dark-ink">{{ row.course.code }} · {{ row.section }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.course.name }} · {{ row.semester }}</p>
        <p class="mt-3 text-h3 font-semibold tabular-nums" :class="row.rate !== null && row.rate < 75 ? 'text-error dark:text-red-300' : 'text-ink dark:text-dark-ink'">
          {{ row.rate === null ? '—' : `${row.rate}%` }}
        </p>
        <div v-if="row.rate !== null" class="mt-2 h-2 overflow-hidden rounded-pill bg-background dark:bg-dark-surface-2" role="presentation">
          <div class="h-full rounded-pill" :class="row.rate < 75 ? 'bg-error' : 'bg-success'" :style="{ width: `${row.rate}%` }" />
        </div>
        <p class="mt-3 text-caption text-muted dark:text-dark-muted">{{ row.present }} present · {{ row.late }} late · {{ row.absent }} absent · {{ row.excused }} excused</p>
      </BaseCard>
    </div>
  </div>
</template>
