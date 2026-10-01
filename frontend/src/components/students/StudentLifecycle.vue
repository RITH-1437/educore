<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import BaseButton from '../BaseButton.vue'
import BaseCard from '../BaseCard.vue'
import BaseInput from '../BaseInput.vue'
import BaseSelect from '../BaseSelect.vue'
import StatusBadge from '../StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'

// Status change + program transfer + program history for one student.
// The server decides which transitions are legal (`allowed_statuses`).
const props = defineProps({
  student: { type: Object, required: true },
  programs: { type: Array, default: () => [] },
})

const { confirm } = useConfirm()
const statusForm = useForm({ status: '', effective_on: '', notes: '' })
const programForm = useForm({ program_id: '', effective_on: '', notes: '' })

const label = (value) => value.charAt(0).toUpperCase() + value.slice(1)
const statusOptions = computed(() => props.student.allowed_statuses.map((value) => ({ value, label: label(value) })))
const programOptions = computed(() =>
  props.programs
    .filter((program) => program.id !== props.student.current_program?.program_id)
    .map((program) => ({ value: program.id, label: `${program.code} — ${program.name}` })),
)
const final = computed(() => props.student.allowed_statuses.length === 0)

const changeStatus = async () => {
  const closing = ['graduated', 'withdrawn'].includes(statusForm.status)
  const ok = await confirm({
    title: `Mark as ${statusForm.status}?`,
    message: closing
      ? `This closes the current program and is final — ${statusForm.status} students cannot be reactivated.`
      : `The student becomes ${statusForm.status}. Only active and graduated students can sign in.`,
    confirmLabel: 'Change status',
    destructive: closing,
  })
  if (ok) statusForm.post(`/students/${props.student.id}/status`, { preserveScroll: true, onSuccess: () => statusForm.reset() })
}

const transfer = () => programForm.post(`/students/${props.student.id}/program`, { preserveScroll: true, onSuccess: () => programForm.reset() })
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <BaseCard title="Status" padding="lg">
      <template #description>Current: <StatusBadge :status="student.status" /> · sign-in {{ student.user?.is_active ? 'allowed' : 'blocked' }}</template>
      <p v-if="final" class="text-small text-muted dark:text-dark-muted">This status is final; no further changes are possible.</p>
      <form v-else class="space-y-4" @submit.prevent="changeStatus">
        <BaseSelect v-model="statusForm.status" label="Change to" :options="statusOptions" placeholder="Select a status" :error="statusForm.errors.status" />
        <BaseInput v-if="['graduated', 'withdrawn'].includes(statusForm.status)" v-model="statusForm.effective_on" name="effective_on" label="Effective date" type="date" hint="Defaults to today." :error="statusForm.errors.effective_on" />
        <BaseInput v-model="statusForm.notes" name="status_notes" label="Notes" :error="statusForm.errors.notes" />
        <BaseButton type="submit" :disabled="!statusForm.status" :loading="statusForm.processing">Change status</BaseButton>
      </form>
    </BaseCard>

    <BaseCard title="Program" padding="lg">
      <template #description>
        <template v-if="student.current_program">Current: <strong>{{ student.current_program.program?.code }}</strong> since {{ student.current_program.started_on }}</template>
        <template v-else>No active program.</template>
      </template>
      <form v-if="student.status === 'active'" class="space-y-4" @submit.prevent="transfer">
        <BaseSelect v-model="programForm.program_id" label="Transfer to" :options="programOptions" placeholder="Select a program" :error="programForm.errors.program_id" />
        <BaseInput v-model="programForm.effective_on" name="transfer_effective_on" label="Effective date" type="date" hint="Defaults to today." :error="programForm.errors.effective_on" />
        <BaseButton type="submit" variant="secondary" :disabled="!programForm.program_id" :loading="programForm.processing">Transfer</BaseButton>
      </form>
      <p v-else class="text-small text-muted dark:text-dark-muted">Only active students can change program.</p>

      <h3 class="mt-6 text-small font-semibold text-ink dark:text-dark-ink">History</h3>
      <ul class="mt-2 divide-y divide-border-default dark:divide-dark-border">
        <li v-for="row in student.program_history" :key="row.id" class="flex flex-wrap items-center justify-between gap-2 py-2 text-small">
          <span><strong>{{ row.program?.code }}</strong> {{ row.started_on }} → {{ row.ended_on ?? 'now' }}</span>
          <StatusBadge :status="row.status" />
        </li>
      </ul>
    </BaseCard>
  </div>
</template>
