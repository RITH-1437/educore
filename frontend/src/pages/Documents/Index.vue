<script setup>
import IconButton from '../../components/IconButton.vue'
import { Ban, Check, Download, FileCheck2, X } from '@lucide/vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
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
  canRevoke: { type: Boolean, default: false },
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

const isInvoiceUnpaid = (row) => Boolean(row.invoice && row.invoice.status !== 'paid')

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
    <PageHeader eyebrow="Operations" title="Document requests" :description="!canProcess ? 'Document requests (read-only).' : canRevoke ? 'Approve, reject and generate official documents. Generated PDFs are built from approved records only.' : 'Approve, reject and generate documents for your department’s students. Revoking an issued document is done by a University Admin.'" />

    <div class="max-w-xs">
      <BaseSelect v-model="status" :options="statusOptions" label="Status" @update:model-value="applyFilter" />
    </div>

    <BaseTable :columns="columns" :rows="requests.data" caption="Document requests" empty-title="No requests" empty-description="Requests submitted by students appear here.">
      <template #cell-student="{ row }">
        <p class="font-medium">{{ row.student.full_name }}</p>
        <p class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student.student_number }}</p>
      </template>
      <template #cell-document="{ row }">
        <div class="flex items-center gap-2">
          <p class="font-medium text-text dark:text-dark-text">
            {{ row.type.name }}<span v-if="row.semester" class="text-muted dark:text-dark-muted"> · {{ row.semester.name }}</span>
          </p>
          <span
            v-if="row.requires_fee && row.fee_amount > 0"
            class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
          >
            ${{ Number(row.fee_amount).toFixed(2) }}
          </span>
          <span
            v-else
            class="inline-flex items-center rounded-full bg-surface-muted px-2 py-0.5 text-xs text-muted dark:bg-dark-surface-elevated dark:text-dark-muted"
          >
            Free
          </span>
        </div>
        <p v-if="row.reason" class="text-caption text-muted dark:text-dark-muted">{{ row.reason }}</p>
        <p v-if="row.rejection_reason" class="text-caption text-error">Rejected: {{ row.rejection_reason }}</p>
        <div v-if="row.invoice" class="mt-1 flex items-center gap-1.5 text-caption">
          <span class="text-muted dark:text-dark-muted">Invoice:</span>
          <Link :href="`/invoices/${row.invoice.id}`" class="font-mono text-primary hover:underline">
            {{ row.invoice.invoice_number }}
          </Link>
          <span
            :class="[
              'rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider',
              row.invoice.status === 'paid'
                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'
            ]"
          >
            {{ row.invoice.status }}
          </span>
        </div>
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
            <IconButton
              v-if="row.status === 'approved'"
              :icon="FileCheck2"
              variant="primary"
              :label="isInvoiceUnpaid(row) ? 'Cannot generate: Invoice pending payment' : 'Generate PDF'"
              :disabled="isInvoiceUnpaid(row)"
              @click="generate(row)"
            />
          </template>
          <IconButton v-if="canRevoke && row.document?.status === 'valid'" :icon="Ban" variant="danger" label="Revoke document" @click="revoke(row)" />
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
