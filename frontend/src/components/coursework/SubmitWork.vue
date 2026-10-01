<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../BaseButton.vue'
import StatusBadge from '../StatusBadge.vue'

// Student upload for one assignment; files are stored privately server-side.
const props = defineProps({
  assignment: { type: Object, required: true },
  submission: { type: Object, default: null },
  acceptedTypes: { type: Array, default: () => [] },
  maxKb: { type: Number, default: 10240 },
})

const input = ref(null)
const form = useForm({ file: null })
const locked = computed(() => ['graded', 'returned'].includes(props.submission?.status))
const accept = computed(() => props.acceptedTypes.map((type) => `.${type}`).join(','))
const maxMb = computed(() => Math.round(props.maxKb / 1024))

const pick = (event) => {
  form.clearErrors()
  form.file = event.target.files?.[0] ?? null
}
const send = () =>
  form.post(`/assignments/${props.assignment.id}/submit`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      if (input.value) input.value.value = ''
    },
  })
</script>

<template>
  <div class="space-y-3">
    <div v-if="submission" class="flex flex-wrap items-center gap-2 text-small">
      <StatusBadge :status="submission.status === 'late' ? 'pending' : submission.status === 'graded' ? 'completed' : 'active'" :label="submission.status === 'late' ? 'Submitted late' : submission.status === 'graded' ? 'Graded' : 'Submitted'" />
      <a v-if="submission.file" :href="submission.file.download_url" class="text-primary underline-offset-2 hover:underline dark:text-dark-primary">{{ submission.file.name }}</a>
      <span v-if="submission.score !== null" class="font-semibold tabular-nums text-ink dark:text-dark-ink">{{ submission.score }} / {{ assignment.max_score }}</span>
    </div>
    <p v-if="submission?.feedback" class="rounded-md bg-background p-3 text-small text-ink dark:bg-dark-surface-2 dark:text-dark-ink">{{ submission.feedback }}</p>

    <form v-if="!locked" class="flex flex-wrap items-center gap-3" @submit.prevent="send">
      <label :for="`file-${assignment.id}`" class="sr-only">File for {{ assignment.title }}</label>
      <input
        :id="`file-${assignment.id}`"
        ref="input"
        type="file"
        :accept="accept"
        class="min-w-0 flex-1 text-small text-muted file:mr-3 file:min-h-9 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:text-small file:font-semibold file:text-primary dark:text-dark-muted dark:file:bg-dark-primary/15 dark:file:text-dark-primary"
        @change="pick"
      />
      <BaseButton type="submit" size="sm" :disabled="!form.file" :loading="form.processing">{{ submission ? 'Replace file' : 'Submit' }}</BaseButton>
    </form>
    <p v-if="!locked" class="text-caption text-muted dark:text-dark-muted">
      {{ acceptedTypes.join(', ').toUpperCase() }} · up to {{ maxMb }} MB<template v-if="assignment.past_due"> · past due — will be marked late</template>
    </p>
    <p v-if="form.errors.file" class="text-small text-error dark:text-red-300" role="alert">{{ form.errors.file }}</p>
  </div>
</template>
