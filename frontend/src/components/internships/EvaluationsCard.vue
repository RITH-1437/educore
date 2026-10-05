<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../BaseButton.vue'
import BaseCard from '../BaseCard.vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseTextarea from '../BaseTextarea.vue'
import EmptyState from '../EmptyState.vue'
import { ratingLabel } from '../../utils/internships'

const props = defineProps({
  internship: { type: Object, required: true },
  ratings: { type: Array, default: () => [] },
  canEvaluate: { type: Boolean, default: false },
})

const open = computed(() => props.canEvaluate && ['in_progress', 'completed'].includes(props.internship.status))
const existing = (type) => props.internship.evaluations.find((e) => e.evaluator_type === type)

const form = useForm({ evaluator_type: 'supervisor', evaluator_name: '', score: '', rating: '', comments: '' })
const load = (type) => {
  const e = existing(type)
  form.evaluator_type = type
  form.evaluator_name = e?.evaluator_name ?? (type === 'supervisor' ? props.internship.supervisor_name ?? '' : '')
  form.score = e?.score ?? ''
  form.rating = e?.rating ?? ''
  form.comments = e?.comments ?? ''
}
load('supervisor')

const typeOptions = [{ value: 'supervisor', label: 'Company supervisor' }, { value: 'academic', label: 'Academic supervisor' }]
const ratingOptions = computed(() => props.ratings.map((r) => ({ value: r, label: ratingLabel(r) })))
const save = () => form.transform((d) => ({ ...d, rating: d.rating || null })).post(`/internships/${props.internship.id}/evaluations`, { preserveScroll: true })
</script>

<template>
  <BaseCard title="Evaluations" padding="lg">
    <EmptyState v-if="!internship.evaluations.length" title="No evaluations yet" description="The company supervisor's and the academic supervisor's evaluations are recorded once the internship has started." />
    <dl v-else class="grid gap-4 sm:grid-cols-2">
      <div v-for="e in internship.evaluations" :key="e.id" class="rounded-lg border border-border-default p-4 dark:border-dark-border">
        <dt class="text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">{{ e.evaluator_type === 'supervisor' ? 'Company supervisor' : 'Academic supervisor' }}<span v-if="e.evaluator_name"> · {{ e.evaluator_name }}</span></dt>
        <dd class="mt-1 text-h4 font-semibold tabular-nums text-ink dark:text-dark-ink">{{ e.score ?? '—' }}<span class="text-small font-normal text-muted dark:text-dark-muted"> / 100 · {{ ratingLabel(e.rating) }}</span></dd>
        <dd v-if="e.comments" class="mt-1 whitespace-pre-line text-small text-muted dark:text-dark-muted">{{ e.comments }}</dd>
      </div>
    </dl>

    <form v-if="open" class="mt-6 space-y-4 border-t border-border-default pt-6 dark:border-dark-border" @submit.prevent="save">
      <div class="grid gap-4 sm:grid-cols-2">
        <BaseSelect :model-value="form.evaluator_type" :options="typeOptions" label="Evaluator" @update:model-value="load" />
        <BaseInput v-model="form.evaluator_name" name="evaluator_name" label="Evaluator name" :error="form.errors.evaluator_name" />
        <BaseInput v-model="form.score" name="score" label="Score (0–100)" type="number" required :error="form.errors.score" />
        <BaseSelect v-model="form.rating" :options="ratingOptions" label="Rating" placeholder="—" :error="form.errors.rating" />
      </div>
      <BaseTextarea v-model="form.comments" name="comments" label="Comments" :rows="3" :error="form.errors.comments" />
      <div class="flex justify-end"><BaseButton type="submit" :loading="form.processing">{{ existing(form.evaluator_type) ? 'Update evaluation' : 'Save evaluation' }}</BaseButton></div>
    </form>
  </BaseCard>
</template>
