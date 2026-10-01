<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import { examWhen, typeLabel } from '../../utils/exams'

const props = defineProps({
  exams: { type: Array, default: () => [] },
  today: { type: String, required: true },
})

// Upcoming first (soonest), then past exams (most recent first).
const upcoming = computed(() => props.exams.filter((exam) => !exam.scheduled_date || exam.scheduled_date >= props.today))
const past = computed(() => props.exams.filter((exam) => exam.scheduled_date && exam.scheduled_date < props.today).reverse())
</script>

<template>
  <Head title="My exams - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Examinations" title="My exams" description="Your exam schedule and results once your lecturers release them." />

    <BaseCard v-if="!exams.length"><EmptyState title="No exams yet" description="Exams appear here once your lecturers schedule them." /></BaseCard>

    <template v-else>
      <section v-for="group in [{ title: 'Upcoming', items: upcoming }, { title: 'Past', items: past }]" :key="group.title" class="space-y-3" :aria-label="`${group.title} exams`">
        <h2 v-if="group.items.length" class="text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">{{ group.title }}</h2>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <BaseCard v-for="exam in group.items" :key="exam.id">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">{{ exam.course.code }} · {{ exam.section_code }}</p>
                <p class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ exam.title }}</p>
              </div>
              <BaseBadge variant="muted" size="sm">{{ typeLabel(exam.exam_type) }}</BaseBadge>
            </div>
            <p class="mt-1 text-small text-muted dark:text-dark-muted">{{ examWhen(exam) }}</p>
            <p class="mt-3 text-small" :class="exam.my_result ? 'text-ink dark:text-dark-ink' : 'text-muted dark:text-dark-muted'">
              <template v-if="exam.my_result">
                Score <span class="font-semibold tabular-nums">{{ exam.my_result.score ?? '—' }} / {{ exam.max_score }}</span> · weight {{ exam.weight }}%
                <span v-if="exam.my_result.remarks" class="block text-muted dark:text-dark-muted">{{ exam.my_result.remarks }}</span>
              </template>
              <template v-else>Weight {{ exam.weight }}% · out of {{ exam.max_score }}{{ exam.is_published ? '' : ' · results not released' }}</template>
            </p>
          </BaseCard>
        </div>
      </section>
    </template>
  </div>
</template>
