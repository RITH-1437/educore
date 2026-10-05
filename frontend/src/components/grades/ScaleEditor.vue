<script setup>
import { CircleAlert, CircleCheck, Plus, Trash2 } from '@lucide/vue'
import { computed } from 'vue'
import BaseButton from '../BaseButton.vue'
import BaseInput from '../BaseInput.vue'
import ErrorAlert from '../ErrorAlert.vue'
import IconButton from '../IconButton.vue'
import { scaleIssues, scaleRanges } from '../../utils/grades'

// Band editor of the grading scale: one header, one row per band. Each band only
// states where it starts; "To" is derived the way the server stores it. The checks
// mirror the server's rules, so a problem is explained before saving.
const props = defineProps({
  /** Inertia form with `bands: [{ grade, min_percentage, grade_point, is_pass }]`. */
  form: { type: Object, required: true },
})
const emit = defineEmits(['save'])

const issues = computed(() => scaleIssues(props.form.bands))
const toByRow = computed(() => Object.fromEntries(scaleRanges(props.form.bands).map((band) => [band.index, band.max])))
const fieldError = (i, field) => props.form.errors[`bands.${i}.${field}`]

const add = () => props.form.bands.push({ grade: '', min_percentage: '', grade_point: '', is_pass: true })
const remove = (i) => props.form.bands.splice(i, 1)
const columns = 'sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,0.8fr)_minmax(0,1fr)_7rem_auto]'
</script>

<template>
  <form class="space-y-4" @submit.prevent="emit('save')">
    <div class="hidden gap-3 px-1 text-caption font-semibold uppercase tracking-wider text-muted sm:grid dark:text-dark-muted" :class="columns" aria-hidden="true">
      <span>Grade</span><span>From</span><span>To</span><span>Grade points</span><span>Result</span><span class="w-8" />
    </div>

    <ul class="space-y-3 sm:space-y-2">
      <li
        v-for="(band, i) in form.bands"
        :key="i"
        class="grid grid-cols-2 items-start gap-3 rounded-lg border border-border-default p-3 sm:border-0 sm:p-1 dark:border-dark-border"
        :class="columns"
      >
        <label class="space-y-1 sm:space-y-0">
          <span class="text-caption font-medium text-muted sm:sr-only dark:text-dark-muted">Grade</span>
          <BaseInput v-model="band.grade" :name="`grade-${i}`" :aria-label="`Grade of band ${i + 1}`" placeholder="A" :error="fieldError(i, 'grade')" />
        </label>
        <label class="space-y-1 sm:space-y-0">
          <span class="text-caption font-medium text-muted sm:sr-only dark:text-dark-muted">From (%)</span>
          <BaseInput v-model="band.min_percentage" :name="`min-${i}`" type="number" min="0" max="100" step="0.01" :aria-label="`Band ${band.grade || i + 1} starts at (%)`" :error="fieldError(i, 'min_percentage')">
            <template #trailing><span class="pr-1 text-small text-muted dark:text-dark-muted">%</span></template>
          </BaseInput>
        </label>
        <div class="space-y-1 sm:space-y-0">
          <span class="text-caption font-medium text-muted sm:hidden dark:text-dark-muted">To</span>
          <p class="flex h-11 items-center px-1 text-small tabular-nums text-muted dark:text-dark-muted" :aria-label="`Band ${band.grade || i + 1} ends at`">{{ toByRow[i] === undefined ? '—' : `${toByRow[i]}%` }}</p>
        </div>
        <label class="space-y-1 sm:space-y-0">
          <span class="text-caption font-medium text-muted sm:sr-only dark:text-dark-muted">Grade points</span>
          <BaseInput v-model="band.grade_point" :name="`point-${i}`" type="number" min="0" max="5" step="0.01" :aria-label="`Grade points of band ${band.grade || i + 1}`" :error="fieldError(i, 'grade_point')" />
        </label>
        <div class="flex items-center sm:h-11">
          <button
            type="button"
            role="switch"
            :aria-checked="band.is_pass"
            :aria-label="`Band ${band.grade || i + 1} counts as a pass`"
            class="inline-flex min-h-8 items-center gap-1.5 rounded-pill px-3 text-small font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none"
            :class="band.is_pass ? 'bg-success/10 text-success hover:bg-success/15 dark:bg-success/15 dark:text-green-300' : 'bg-error/10 text-error hover:bg-error/15 dark:bg-error/15 dark:text-red-300'"
            @click="band.is_pass = !band.is_pass"
          >
            <span class="h-1.5 w-1.5 rounded-pill bg-current" aria-hidden="true" />{{ band.is_pass ? 'Pass' : 'Fail' }}
          </button>
        </div>
        <div class="flex items-center justify-end sm:h-11">
          <IconButton :icon="Trash2" variant="danger" :disabled="form.bands.length <= 2" :label="`Remove grade ${band.grade || i + 1}`" @click="remove(i)" />
        </div>
      </li>
    </ul>

    <IconButton :icon="Plus" size="md" label="Add band" :disabled="form.bands.length >= 20" @click="add" />

    <!-- Live checks: the server's rules, before saving. -->
    <div v-if="issues.length" class="rounded-lg bg-warning/10 p-4 text-small text-ink dark:bg-warning/15 dark:text-dark-ink" role="status">
      <p class="flex items-center gap-2 font-semibold"><CircleAlert class="h-4 w-4 text-warning" aria-hidden="true" />Fix before saving</p>
      <ul class="mt-2 list-disc space-y-1 pl-10">
        <li v-for="issue in issues" :key="issue">{{ issue }}</li>
      </ul>
    </div>
    <p v-else-if="form.isDirty" class="flex items-center gap-2 text-small text-success dark:text-green-300" role="status">
      <CircleCheck class="h-4 w-4" aria-hidden="true" />The scale checks out — ready to save.
    </p>

    <ErrorAlert v-if="form.errors.bands" title="Could not save" :message="form.errors.bands" />

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-border-default pt-4 dark:border-dark-border">
      <span v-if="form.isDirty" class="mr-auto text-small text-muted dark:text-dark-muted">Unsaved changes</span>
      <BaseButton v-if="form.isDirty" variant="ghost" :disabled="form.processing" @click="form.reset(); form.clearErrors()">Discard</BaseButton>
      <BaseButton type="submit" :loading="form.processing" :disabled="issues.length > 0 || !form.isDirty">Save scale</BaseButton>
    </div>
  </form>
</template>
