<script setup>
import { Head } from '@inertiajs/vue3'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'

defineProps({
  sections: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="Attendance - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Teaching" title="Teaching" description="Sections you teach this term. Take attendance, manage assignments, or plan exams and enter results." />

    <BaseCard v-if="!sections.length"><EmptyState title="No sections assigned" description="Sections appear here once an administrator assigns you to them." /></BaseCard>

    <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <BaseCard v-for="section in sections" :key="section.id">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ section.course.code }} · {{ section.code }}</p>
            <p class="text-small text-muted dark:text-dark-muted">{{ section.course.name }}</p>
          </div>
          <BaseBadge :variant="section.role === 'primary' ? 'primary' : 'muted'" size="sm">{{ section.role }}</BaseBadge>
        </div>
        <p class="mt-3 text-caption text-muted dark:text-dark-muted">{{ section.semester }} · {{ section.students }} students</p>
        <div class="mt-4 flex flex-wrap gap-2">
          <BaseButton :href="`/attendance/sections/${section.id}`" size="sm">Take attendance</BaseButton>
          <BaseButton :href="`/coursework/sections/${section.id}`" size="sm" variant="secondary">Assignments</BaseButton>
          <BaseButton :href="`/exams/sections/${section.id}`" size="sm" variant="secondary">Exams</BaseButton>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
