<script setup>
import { Head } from '@inertiajs/vue3'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import InternshipForm from '../../components/internships/InternshipForm.vue'
import { formatDate, statusBadge } from '../../utils/internships'

defineProps({
  current: { type: Object, default: null },
  history: { type: Array, default: () => [] },
  companies: { type: Array, default: () => [] },
})

const NEXT_STEP = {
  draft: 'Check the details, then submit your application for review.',
  submitted: 'Your application is waiting for the university office.',
  under_review: 'The university office is reviewing your application.',
  approved: 'Approved — submit an initial report when you begin.',
  in_progress: 'Under way — submit progress reports and, at the end, your final report.',
}
</script>

<template>
  <Head title="My internship - EduCore" />
  <div class="mx-auto max-w-4xl space-y-6">
    <PageHeader eyebrow="Services" title="My internship" description="Apply for an internship, follow its review and submit your reports." />

    <BaseCard v-if="current" padding="lg">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
          <p class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ current.position_title }}</p>
          <p class="text-small text-muted dark:text-dark-muted">{{ current.company.name }} · {{ formatDate(current.start_date) }} – {{ formatDate(current.end_date) }}</p>
        </div>
        <StatusBadge v-bind="statusBadge(current.status)" />
      </div>
      <p class="mt-3 text-small text-ink dark:text-dark-ink">{{ NEXT_STEP[current.status] }}</p>
      <div class="mt-4"><BaseButton :href="`/internships/${current.id}`">Open internship</BaseButton></div>
    </BaseCard>

    <BaseCard v-else title="Apply for an internship" padding="lg">
      <template #description>Saved as a draft first; you submit it for review when it is ready.</template>
      <InternshipForm :companies="companies" submit-label="Save draft" />
    </BaseCard>

    <section v-if="history.length" class="space-y-3" aria-label="Past applications">
      <h2 class="text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">History</h2>
      <BaseCard v-for="item in history" :key="item.id" :href="`/internships/${item.id}`" hoverable>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="text-small text-ink dark:text-dark-ink">{{ item.position_title }} · {{ item.company.name }}</p>
          <StatusBadge v-bind="statusBadge(item.status)" />
        </div>
      </BaseCard>
    </section>
  </div>
</template>
