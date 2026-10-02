<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../BaseButton.vue'
import BaseCard from '../BaseCard.vue'
import BaseInput from '../BaseInput.vue'
import ErrorAlert from '../ErrorAlert.vue'
import { COMPONENTS, componentLabel } from '../../utils/grades'

const props = defineProps({
  courseId: { type: Number, required: true },
  config: { type: Object, required: true },
})

const form = useForm(Object.fromEntries(COMPONENTS.map((component) => [`${component}_weight`, props.config[`${component}_weight`]])))
const total = computed(() => COMPONENTS.reduce((sum, component) => sum + Number(form[`${component}_weight`] || 0), 0))
const save = () => form.put(`/courses/${props.courseId}/grading-config`, { preserveScroll: true })
</script>

<template>
  <BaseCard title="Grading weights" padding="lg">
    <template #description>
      How the course grade is built (Grades &amp; GPA). Coursework covers assignments plus quiz and other exams.
      <span v-if="config.is_default">These are the defaults; save to customise this course.</span>
    </template>
    <form class="space-y-4" @submit.prevent="save">
      <div class="grid gap-4 sm:grid-cols-5">
        <BaseInput
          v-for="component in COMPONENTS"
          :key="component"
          v-model="form[`${component}_weight`]"
          :name="`${component}_weight`"
          :label="`${componentLabel(component)} (%)`"
          type="number"
          required
          :error="form.errors[`${component}_weight`]"
        />
      </div>
      <ErrorAlert v-if="form.errors.weights" title="Could not save" :message="form.errors.weights" />
      <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-small text-muted dark:text-dark-muted">
          Total <span class="font-semibold tabular-nums" :class="total === 100 ? 'text-ink dark:text-dark-ink' : 'text-error'">{{ total }}%</span> of 100%
        </p>
        <BaseButton type="submit" :loading="form.processing" :disabled="total !== 100">Save weights</BaseButton>
      </div>
    </form>
  </BaseCard>
</template>
