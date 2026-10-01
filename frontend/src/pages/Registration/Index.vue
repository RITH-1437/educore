<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  student: { type: Object, required: true },
  enrollments: { type: Array, default: () => [] },
  sections: { type: Array, default: () => [] },
  maxCredits: { type: Number, default: 24 },
})

const { confirm } = useConfirm()
const current = computed(() => props.enrollments.filter((e) => ['pending', 'confirmed'].includes(e.status)))
const credits = computed(() => current.value.reduce((sum, e) => sum + (e.section?.course?.credits ?? 0), 0))
const active = computed(() => props.student.status === 'active')

// Why a section cannot be chosen, shown instead of the button.
const blocker = (section) => {
  if (section.enrolled) return 'Enrolled'
  if (!active.value) return `Not available while ${props.student.status}`
  if (!section.registration_open) return 'Registration closed'
  if (section.missing_prerequisites.length) return `Needs ${section.missing_prerequisites.join(', ')}`
  if (section.seats <= 0) return 'Full'
  if (credits.value + section.course.credits > props.maxCredits) return 'Over credit limit'
  return null
}

const enroll = (section) => router.post('/registration', { section_id: section.id }, { preserveScroll: true })
const drop = async (enrollment) => {
  if (await confirm({ title: `Drop ${enrollment.section?.course?.code}?`, message: 'Your seat is released. The record is kept in your history.', confirmLabel: 'Drop', destructive: true })) {
    router.post(`/registration/${enrollment.id}/drop`, {}, { preserveScroll: true })
  }
}
</script>

<template>
  <Head title="Course registration - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Registration" title="Course registration" :description="`${student.full_name} · ${student.student_number}`" />

    <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
      <BaseCard title="My courses this term" padding="lg">
        <template #description>{{ credits }} of {{ maxCredits }} credits</template>
        <div class="mb-4 h-2 overflow-hidden rounded-pill bg-background dark:bg-dark-surface-2" role="presentation">
          <div class="h-full rounded-pill bg-primary dark:bg-dark-primary" :style="{ width: `${Math.min(100, (credits / maxCredits) * 100)}%` }" />
        </div>
        <ul v-if="current.length" class="divide-y divide-border-default dark:divide-dark-border">
          <li v-for="enrollment in current" :key="enrollment.id" class="flex items-center justify-between gap-3 py-3 first:pt-0">
            <div>
              <p class="text-small font-semibold text-ink dark:text-dark-ink">{{ enrollment.section?.course?.code }} · {{ enrollment.section?.code }}</p>
              <p class="text-caption text-muted dark:text-dark-muted">{{ enrollment.section?.course?.name }} · {{ enrollment.section?.course?.credits }} cr</p>
            </div>
            <BaseButton size="sm" variant="ghost" class="text-error dark:text-red-300" @click="drop(enrollment)">Drop</BaseButton>
          </li>
        </ul>
        <EmptyState v-else title="No courses yet" description="Pick sections from the list." />
      </BaseCard>

      <BaseCard title="Open sections" padding="lg">
        <template #description>Sections open for registration this term.</template>
        <ul v-if="sections.length" class="divide-y divide-border-default dark:divide-dark-border">
          <li v-for="section in sections" :key="section.id" class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0">
            <div class="min-w-0">
              <p class="text-small font-semibold text-ink dark:text-dark-ink">{{ section.course.code }} · Section {{ section.code }} <span class="font-normal text-muted dark:text-dark-muted">— {{ section.course.name }}</span></p>
              <p class="text-caption text-muted dark:text-dark-muted">{{ section.course.credits }} cr · {{ section.seats }} seats left<template v-if="section.lecturer"> · {{ section.lecturer }}</template></p>
            </div>
            <BaseBadge v-if="blocker(section)" :variant="section.enrolled ? 'success' : 'muted'" size="sm">{{ blocker(section) }}</BaseBadge>
            <BaseButton v-else size="sm" @click="enroll(section)">Enroll</BaseButton>
          </li>
        </ul>
        <EmptyState v-else title="Nothing open right now" description="Registration opens when the semester's sections are published." />
      </BaseCard>
    </div>

    <BaseCard v-if="enrollments.length > current.length" title="History" padding="lg">
      <ul class="divide-y divide-border-default dark:divide-dark-border">
        <li v-for="enrollment in enrollments.filter((e) => !['pending', 'confirmed'].includes(e.status))" :key="enrollment.id" class="flex items-center justify-between gap-3 py-2 text-small">
          <span>{{ enrollment.section?.course?.code }} · {{ enrollment.semester?.academic_year }} {{ enrollment.semester?.name }}</span>
          <StatusBadge :status="enrollment.status" />
        </li>
      </ul>
    </BaseCard>
  </div>
</template>
