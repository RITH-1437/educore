<script setup>
import IconButton from '../../components/IconButton.vue'
import { Award, ClipboardList, FileCheck, UserCheck } from '@lucide/vue'
import { Head } from '@inertiajs/vue3'
import BaseBadge from '../../components/BaseBadge.vue'
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
        <div class="mt-4 flex flex-wrap gap-1">
          <IconButton :icon="UserCheck" :href="`/attendance/sections/${section.id}`" variant="primary" label="Take attendance" />
          <IconButton :icon="ClipboardList" :href="`/coursework/sections/${section.id}`" label="Assignments" />
          <IconButton :icon="FileCheck" :href="`/exams/sections/${section.id}`" label="Exams" />
          <IconButton :icon="Award" :href="`/grades/sections/${section.id}`" label="Grades" />
        </div>
      </BaseCard>
    </div>
  </div>
</template>
