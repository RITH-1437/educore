<script setup>
import IconButton from '../../components/IconButton.vue'
import { Ban, Check, Download, FileCheck2, X } from '@lucide/vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'
import { documentBadge, formatDate, requestBadge } from '../../utils/documents'

const props = defineProps({
  requests: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  statuses: { type: Array, default: () => [] },
  canProcess: { type: Boolean, default: false },
})

const { confirm } = useConfirm()
const status = ref(props.filters.status ?? '')
const statusOptions = computed(() => [{ value: '', label: 'All statuses' }, ...props.statuses.map((value) => ({ value, label: requestBadge(value).label }))])
const applyFilter = () => router.get('/documents', { filters: status.value ? { status: status.value } : undefined }, { preserveState: true, replace: true })

const post = (url) => router.post(url, {}, { preserveScroll: true })
const approve = (row) => post(`/document-requests/${row.id}/approve`)
const generate = (row) => post(`/document-requests/${row.id}/generate`)
const revoke = async (row) => {
  if (await confirm({ title: 'Revoke this document?', message: 'Verification will report it as revoked. The student can request a new one.', confirmLabel: 'Revoke', destructive: true })) {
    post(`/documents/${row.document.id}/revoke`)
  }
}

const rejecting = ref(null)
const rejectForm = useForm({ rejection_reason: '' })
const openReject = (row) => {
  rejecting.value = row
  rejectForm.reset()
  rejectForm.clearErrors()
}
const reject = () => rejectForm.post(`/document-requests/${rejecting.value.id}/reject`, { preserveScroll: true, onSuccess: () => (rejecting.value = null) })
const showReject = computed({ get: () => rejecting.value !== null, set: (open) => { if (!open) rejecting.value = null } })

const columns = [
  { key: 'student', label: 'Student' },
  { key: 'document', label: 'Document' },
  { key: 'submitted', label: 'Requested' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]
</script>

<template>
  <Head title="Documents - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Operations" title="Document requests" :description="canProcess ? 'Approve, reject and generate official documents. Generated PDFs are built from approved records only.' : 'Document requests across the university (read-only).'" />

    <div class="max-w-xs">
      <BaseSelect v-model="status" :options="statusOptions" label="Status" @update:model-value="applyFilter" />
    </div>

    <BaseTable :columns="columns" :rows="requests.data" caption="Document requests" empty-title="No requests" empty-description="Requests submitted by students appear here.">
      <template #cell-student="{ row }">
        <p class="font-medium">{{ row.student.full_name }}</p>
        <p class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student.student_number }}</p>
      </template>
      <template #cell-document="{ row }">
        <p>{{ row.type.name }}<span v-if="row.semester" class="text-muted dark:text-dark-muted"> · {{ row.semester.name }}</span></p>
        <p v-if="row.reason" class="text-caption text-muted dark:text-dark-muted">{{ row.reason }}</p>
        <p v-if="row.rejection_reason" class="text-caption text-error">Rejected: {{ row.rejection_reason }}</p>
      </template>
      <template #cell-submitted="{ row }">{{ formatDate(row.submitted_at) }}</template>
      <template #cell-status="{ row }">
        <div class="flex flex-wrap gap-1">
          <StatusBadge v-bind="requestBadge(row.status)" />
          <StatusBadge v-if="row.document" v-bind="documentBadge(row.document.status)" />
        </div>
      </template>
      <template #cell-actions="{ row }">
        <div class="flex flex-wrap justify-end gap-1">
          <IconButton v-if="row.document" :icon="Download" :href="`/documents/${row.document.id}/download`" native label="Download PDF" />
          <template v-if="canProcess">
            <IconButton v-if="row.status === 'pending'" :icon="Check" variant="success" label="Approve request" @click="approve(row)" />
            <IconButton v-if="row.status === 'pending'" :icon="X" variant="danger" label="Reject request" @click="openReject(row)" />
            <IconButton v-if="row.status === 'approved'" :icon="FileCheck2" variant="primary" label="Generate PDF" @click="generate(row)" />
            <IconButton v-if="row.document?.status === 'valid'" :icon="Ban" variant="danger" label="Revoke document" @click="revoke(row)" />
          </template>
        </div>
      </template>
    </BaseTable>

    <Pagination :links="requests.meta?.links ?? []" />

    <BaseModal v-model="showReject" title="Reject request">
      <form id="reject-form" @submit.prevent="reject">
        <BaseTextarea v-model="rejectForm.rejection_reason" name="rejection_reason" label="Reason shown to the student" required :rows="3" :error="rejectForm.errors.rejection_reason" />
      </form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" @click="showReject = false">Cancel</BaseButton>
          <BaseButton type="submit" form="reject-form" variant="danger" :loading="rejectForm.processing">Reject</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
