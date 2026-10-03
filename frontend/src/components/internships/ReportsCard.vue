<script setup>
import IconButton from '../IconButton.vue'
import { ClipboardCheck, Pencil } from '@lucide/vue'
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../BaseButton.vue'
import BaseCard from '../BaseCard.vue'
import BaseInput from '../BaseInput.vue'
import BaseModal from '../BaseModal.vue'
import BaseSelect from '../BaseSelect.vue'
import BaseTextarea from '../BaseTextarea.vue'
import EmptyState from '../EmptyState.vue'
import StatusBadge from '../StatusBadge.vue'
import { formatDate, reportTypeLabel } from '../../utils/internships'

const props = defineProps({
  internship: { type: Object, required: true },
  canSubmit: { type: Boolean, default: false },
  canReview: { type: Boolean, default: false },
})

// Initial / progress once approved; final once in progress (server enforces).
const typeOptions = computed(() => {
  const types = props.internship.status === 'in_progress' ? ['initial', 'progress', 'final'] : ['initial', 'progress']
  return types.map((t) => ({ value: t, label: reportTypeLabel(t) }))
})
const open = computed(() => props.canSubmit && ['approved', 'in_progress'].includes(props.internship.status))

const form = useForm({ report_type: 'progress', title: '', summary: '', file: null })
const submit = () => form.post(`/internships/${props.internship.id}/reports`, { preserveScroll: true, forceFormData: true, onSuccess: () => form.reset() })

const reviewing = ref(null)
const reviewForm = useForm({ reviewer_comment: '' })
const showReview = computed({ get: () => reviewing.value !== null, set: (v) => { if (!v) reviewing.value = null } })
const openReview = (report) => {
  reviewing.value = report
  reviewForm.reviewer_comment = report.reviewer_comment ?? ''
}
const review = () => reviewForm.post(`/internship-reports/${reviewing.value.id}/review`, { preserveScroll: true, onSuccess: () => (reviewing.value = null) })
</script>

<template>
  <BaseCard title="Reports" padding="lg">
    <EmptyState v-if="!internship.reports.length" title="No reports yet" description="Initial and progress reports after approval; the final report once the internship is under way." />
    <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
      <li v-for="report in internship.reports" :key="report.id" class="flex flex-wrap items-start justify-between gap-3 py-3">
        <div class="min-w-0">
          <p class="text-small font-medium text-ink dark:text-dark-ink">{{ reportTypeLabel(report.report_type) }} · {{ report.title }}</p>
          <p class="text-caption text-muted dark:text-dark-muted">
            {{ formatDate(report.submitted_at) }}
            <template v-if="report.file"> · <a :href="`/internship-reports/${report.id}/file`" class="text-primary underline-offset-2 hover:underline dark:text-dark-primary">{{ report.file.name }}</a></template>
          </p>
          <p v-if="report.summary" class="mt-1 whitespace-pre-line text-small text-muted dark:text-dark-muted">{{ report.summary }}</p>
          <p v-if="report.reviewer_comment" class="mt-1 text-small text-ink dark:text-dark-ink">Reviewer: {{ report.reviewer_comment }}</p>
        </div>
        <div class="flex items-center gap-2">
          <StatusBadge :status="report.status === 'reviewed' ? 'completed' : 'pending'" :label="report.status === 'reviewed' ? 'Reviewed' : 'Submitted'" />
          <IconButton v-if="canReview" :icon="report.status === 'reviewed' ? Pencil : ClipboardCheck" :label="report.status === 'reviewed' ? 'Edit review' : 'Review report'" @click="openReview(report)" />
        </div>
      </li>
    </ul>

    <form v-if="open" class="mt-6 space-y-4 border-t border-border-default pt-6 dark:border-dark-border" @submit.prevent="submit">
      <p class="text-small font-semibold text-ink dark:text-dark-ink">Submit a report</p>
      <div class="grid gap-4 sm:grid-cols-[1fr_2fr]">
        <BaseSelect v-model="form.report_type" :options="typeOptions" label="Type" :error="form.errors.report_type" />
        <BaseInput v-model="form.title" name="title" label="Title" required :error="form.errors.title" />
      </div>
      <BaseTextarea v-model="form.summary" name="summary" label="Summary" :rows="4" :error="form.errors.summary" />
      <div>
        <label for="report-file" class="block text-small font-medium text-ink dark:text-dark-ink">File (optional, PDF or DOCX)</label>
        <input id="report-file" type="file" accept=".pdf,.docx" class="mt-2 block w-full text-small text-muted file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-primary dark:text-dark-muted" @input="form.file = $event.target.files[0]" />
        <p v-if="form.errors.file" class="mt-1 text-small text-error">{{ form.errors.file }}</p>
      </div>
      <div class="flex justify-end"><BaseButton type="submit" :loading="form.processing">Submit report</BaseButton></div>
    </form>

    <BaseModal v-model="showReview" title="Review report">
      <form id="review-form" @submit.prevent="review">
        <BaseTextarea v-model="reviewForm.reviewer_comment" name="reviewer_comment" label="Comment for the student (optional)" :rows="3" :error="reviewForm.errors.reviewer_comment" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showReview = false">Cancel</BaseButton>
          <BaseButton type="submit" form="review-form" :loading="reviewForm.processing">Mark reviewed</BaseButton>
        </div>
      </template>
    </BaseModal>
  </BaseCard>
</template>
