<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { X } from '@lucide/vue'
import BaseBadge from '../BaseBadge.vue'
import BaseButton from '../BaseButton.vue'
import BaseCard from '../BaseCard.vue'
import BaseSelect from '../BaseSelect.vue'
import EmptyState from '../EmptyState.vue'
import StatusBadge from '../StatusBadge.vue'

// Lists a course's prerequisites and lets managers add/remove them. Duplicate,
// archived and cyclic prerequisites are rejected server-side; the message is
// shown under the select.
const props = defineProps({
  course: { type: Object, required: true },
  options: { type: Array, default: () => [] },
  canManage: { type: Boolean, default: false },
})

const form = useForm({ prerequisite_course_id: '', is_strict: true })

const selectOptions = computed(() => props.options.map((option) => ({ value: option.id, label: `${option.code} — ${option.name}` })))

const add = () =>
  form.post(`/courses/${props.course.id}/prerequisites`, {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  })

const remove = (prerequisite) =>
  router.delete(`/courses/${props.course.id}/prerequisites/${prerequisite.id}`, { preserveScroll: true })
</script>

<template>
  <BaseCard title="Prerequisites" padding="lg">
    <template #description>Courses a student must complete first. A course can never depend on itself, directly or through a chain.</template>

    <ul v-if="course.prerequisites?.length" class="divide-y divide-border-default dark:divide-dark-border">
      <li v-for="prerequisite in course.prerequisites" :key="prerequisite.id" class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
        <div class="min-w-0">
          <p class="text-small font-medium text-ink dark:text-dark-ink">
            <span class="font-semibold">{{ prerequisite.code }}</span> — {{ prerequisite.name }}
          </p>
          <p class="mt-1 flex flex-wrap items-center gap-2 text-caption text-muted dark:text-dark-muted">
            {{ prerequisite.credits }} credits
            <BaseBadge :variant="prerequisite.is_strict ? 'primary' : 'muted'" size="sm">{{ prerequisite.is_strict ? 'Required' : 'Recommended' }}</BaseBadge>
            <StatusBadge v-if="prerequisite.status !== 'active'" :status="prerequisite.status" />
          </p>
        </div>
        <button
          v-if="canManage"
          type="button"
          class="inline-flex min-h-9 items-center gap-1 rounded-md px-2 text-small font-semibold text-error hover:bg-error/5 focus-visible:outline-2 focus-visible:outline-error dark:text-red-300"
          :aria-label="`Remove prerequisite ${prerequisite.code}`"
          @click="remove(prerequisite)"
        >
          <X class="h-4 w-4" aria-hidden="true" /> Remove
        </button>
      </li>
    </ul>
    <EmptyState v-else title="No prerequisites" description="Students can take this course without completing another one first." />

    <form v-if="canManage" class="mt-6 grid gap-3 border-t border-border-default pt-5 dark:border-dark-border sm:grid-cols-[1fr_auto] sm:items-end" @submit.prevent="add">
      <BaseSelect v-model="form.prerequisite_course_id" label="Add a prerequisite" :options="selectOptions" placeholder="Select a course" :error="form.errors.prerequisite_course_id" />
      <BaseButton type="submit" :loading="form.processing" :disabled="!form.prerequisite_course_id">Add</BaseButton>
      <label class="flex min-h-11 items-center gap-2 text-small text-ink dark:text-dark-ink sm:col-span-2">
        <input v-model="form.is_strict" type="checkbox" class="h-4 w-4 rounded-sm border-border-muted accent-primary focus-visible:outline-2 focus-visible:outline-primary" />
        Must be completed before enrollment
      </label>
    </form>
  </BaseCard>
</template>
