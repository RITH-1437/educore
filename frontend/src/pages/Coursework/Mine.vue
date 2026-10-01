<script setup>
import { Head } from '@inertiajs/vue3'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import SubmitWork from '../../components/coursework/SubmitWork.vue'
import { formatDue } from '../../utils/coursework'

defineProps({
  assignments: { type: Array, default: () => [] },
  acceptedTypes: { type: Array, default: () => [] },
  maxKb: { type: Number, default: 10240 },
})
</script>

<template>
  <Head title="My assignments - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Coursework" title="My assignments" description="Published assignments across your current sections, soonest due first." />

    <BaseCard v-if="!assignments.length"><EmptyState title="No assignments yet" description="Assignments appear here once your lecturers publish them." /></BaseCard>

    <div v-else class="grid gap-4 lg:grid-cols-2">
      <BaseCard v-for="assignment in assignments" :key="assignment.id">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">{{ assignment.course.code }} · {{ assignment.section_code }}</p>
            <p class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ assignment.title }}</p>
          </div>
          <BaseBadge variant="muted" size="sm">{{ assignment.assignment_type }}</BaseBadge>
        </div>
        <p class="mt-1 text-small" :class="assignment.past_due ? 'text-error dark:text-red-300' : 'text-muted dark:text-dark-muted'">
          Due {{ formatDue(assignment.due_at) }} · {{ assignment.max_score }} points
        </p>
        <p v-if="assignment.description" class="mt-3 text-small text-ink dark:text-dark-ink">{{ assignment.description }}</p>
        <p v-if="assignment.instructions" class="mt-2 whitespace-pre-line text-small text-muted dark:text-dark-muted">{{ assignment.instructions }}</p>
        <div class="mt-4 border-t border-border-default pt-4 dark:border-dark-border">
          <SubmitWork :assignment="assignment" :submission="assignment.my_submission" :accepted-types="acceptedTypes" :max-kb="maxKb" />
        </div>
      </BaseCard>
    </div>
  </div>
</template>
