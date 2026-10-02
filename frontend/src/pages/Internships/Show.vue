<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import EvaluationsCard from '../../components/internships/EvaluationsCard.vue'
import InternshipForm from '../../components/internships/InternshipForm.vue'
import ReportsCard from '../../components/internships/ReportsCard.vue'
import { useConfirm } from '../../composables/useConfirm'
import { FINAL, formatDate, statusBadge } from '../../utils/internships'

const props = defineProps({
  internship: { type: Object, required: true },
  companies: { type: Array, default: () => [] },
  canProcess: { type: Boolean, default: false },
  isOwner: { type: Boolean, default: false },
  ratings: { type: Array, default: () => [] },
})

const { confirm } = useConfirm()
const i = computed(() => props.internship)
const status = computed(() => i.value.status)
const editable = computed(() => (props.isOwner && status.value === 'draft') || (props.canProcess && !FINAL.includes(status.value)))
const showEdit = ref(false)

const go = (action, data = {}) => router.post(`/internships/${i.value.id}/${action}`, data, { preserveScroll: true })
const ask = async (action, title, message, confirmLabel, destructive = false) => {
  if (await confirm({ title, message, confirmLabel, destructive })) go(action)
}

// Decisions that need text (reject / cancel by staff) or allow a note (approve / complete).
const decision = ref(null)
const decisionForm = useForm({ text: '' })
const DECISIONS = {
  approve: { title: 'Approve internship', label: 'Note for the student (optional)', field: 'note', button: 'Approve', required: false },
  reject: { title: 'Reject application', label: 'Reason shown to the student', field: 'reason', button: 'Reject', required: true, destructive: true },
  complete: { title: 'Mark completed', label: 'Note (optional)', field: 'note', button: 'Complete', required: false },
  cancel: { title: 'Cancel internship', label: 'Reason (early termination)', field: 'reason', button: 'Cancel internship', required: true, destructive: true },
}
const showDecision = computed({ get: () => decision.value !== null, set: (v) => { if (!v) decision.value = null } })
const openDecision = (action) => {
  decision.value = action
  decisionForm.reset()
  decisionForm.clearErrors()
}
const decide = () => {
  const d = DECISIONS[decision.value]
  decisionForm.transform((data) => ({ [d.field]: data.text || null })).post(`/internships/${i.value.id}/${decision.value}`, {
    preserveScroll: true,
    onSuccess: () => (decision.value = null),
    onError: (errors) => decisionForm.setError('text', errors[d.field]),
  })
}
</script>

<template>
  <Head :title="`${internship.position_title} - EduCore`" />
  <div class="mx-auto max-w-5xl space-y-6">
    <PageHeader eyebrow="Internship" :title="internship.position_title" :description="`${internship.company.name} · ${internship.student.full_name} (${internship.student.student_number})`">
      <template #actions>
        <BaseButton :href="isOwner ? '/my-internships' : '/internships'" variant="secondary">Back</BaseButton>
        <BaseButton v-if="editable" variant="secondary" @click="showEdit = true">Edit</BaseButton>
        <!-- Student -->
        <template v-if="isOwner">
          <BaseButton v-if="status === 'draft'" @click="ask('submit', 'Submit application?', 'The university office will review it. You can no longer edit it after submitting.', 'Submit')">Submit for review</BaseButton>
          <BaseButton v-if="['draft', 'submitted', 'under_review'].includes(status)" variant="ghost" @click="ask('cancel', 'Withdraw application?', 'You can start a new application afterwards.', 'Withdraw', true)">Withdraw</BaseButton>
        </template>
        <!-- Staff -->
        <template v-if="canProcess">
          <BaseButton v-if="status === 'submitted'" variant="secondary" @click="go('review')">Start review</BaseButton>
          <BaseButton v-if="['submitted', 'under_review'].includes(status)" @click="openDecision('approve')">Approve</BaseButton>
          <BaseButton v-if="['submitted', 'under_review'].includes(status)" variant="ghost" @click="openDecision('reject')">Reject</BaseButton>
          <BaseButton v-if="status === 'approved'" @click="ask('start', 'Mark as started?', 'The student can then submit the final report.', 'Mark started')">Mark started</BaseButton>
          <BaseButton v-if="status === 'in_progress'" @click="openDecision('complete')">Mark completed</BaseButton>
          <BaseButton v-if="['approved', 'in_progress'].includes(status)" variant="ghost" @click="openDecision('cancel')">Cancel</BaseButton>
        </template>
      </template>
    </PageHeader>

    <BaseCard padding="lg">
      <div class="grid gap-4 sm:grid-cols-4">
        <div><p class="text-caption text-muted dark:text-dark-muted">Status</p><StatusBadge class="mt-1" v-bind="statusBadge(status)" /></div>
        <div><p class="text-caption text-muted dark:text-dark-muted">Dates</p><p class="text-small text-ink dark:text-dark-ink">{{ formatDate(internship.start_date) }} – {{ formatDate(internship.end_date) }}</p></div>
        <div><p class="text-caption text-muted dark:text-dark-muted">Supervisor</p><p class="text-small text-ink dark:text-dark-ink">{{ internship.supervisor_name }}</p><p class="text-caption text-muted dark:text-dark-muted">{{ [internship.supervisor_email, internship.supervisor_phone].filter(Boolean).join(' · ') }}</p></div>
        <div><p class="text-caption text-muted dark:text-dark-muted">Submitted</p><p class="text-small text-ink dark:text-dark-ink">{{ formatDate(internship.submitted_at) }}</p></div>
      </div>
      <p v-if="internship.description" class="mt-4 whitespace-pre-line text-small text-muted dark:text-dark-muted">{{ internship.description }}</p>
      <div v-if="internship.notes" class="mt-4 rounded-md bg-surface px-4 py-3 dark:bg-dark-surface-2">
        <p class="text-caption font-semibold uppercase tracking-wider text-muted dark:text-dark-muted">Review notes</p>
        <p class="mt-1 whitespace-pre-line text-small text-ink dark:text-dark-ink">{{ internship.notes }}</p>
      </div>
    </BaseCard>

    <ReportsCard :internship="internship" :can-submit="isOwner" :can-review="canProcess" />
    <EvaluationsCard v-if="canProcess || internship.evaluations.length" :internship="internship" :ratings="ratings" :can-evaluate="canProcess" />

    <BaseModal v-model="showEdit" title="Edit internship" size="lg">
      <InternshipForm :internship="internship" :companies="companies" submit-label="Save changes" @saved="showEdit = false" />
    </BaseModal>

    <BaseModal v-model="showDecision" :title="decision ? DECISIONS[decision].title : ''">
      <form v-if="decision" id="decision-form" @submit.prevent="decide">
        <BaseTextarea v-model="decisionForm.text" name="decision_text" :label="DECISIONS[decision].label" :required="DECISIONS[decision].required" :rows="3" :error="decisionForm.errors.text" />
      </form>
      <template #footer>
        <div v-if="decision" class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showDecision = false">Close</BaseButton>
          <BaseButton type="submit" form="decision-form" :variant="DECISIONS[decision].destructive ? 'danger' : 'primary'" :loading="decisionForm.processing">{{ DECISIONS[decision].button }}</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
