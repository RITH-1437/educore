<script setup>
import IconButton from '../../components/IconButton.vue'
import { Download } from '@lucide/vue'
import { Head, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import EmptyState from '../../components/EmptyState.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { documentBadge, fileSize, formatDate, requestBadge } from '../../utils/documents'

const props = defineProps({
  requests: { type: Object, required: true },
  types: { type: Array, default: () => [] },
  semesters: { type: Array, default: () => [] },
})

const form = useForm({ document_type_id: props.types[0]?.id ?? '', semester_id: '', reason: '' })
const selectedType = computed(() => props.types.find((type) => type.id === Number(form.document_type_id)))
const typeOptions = computed(() => props.types.map((type) => ({ value: type.id, label: type.name })))
const semesterOptions = computed(() => props.semesters.map((semester) => ({ value: semester.id, label: semester.name })))
const submit = () =>
  form
    .transform((data) => ({ ...data, semester_id: selectedType.value?.needs_semester ? data.semester_id || null : null, reason: data.reason || null }))
    .post('/my-documents', { preserveScroll: true, onSuccess: () => form.reset('reason', 'semester_id') })
</script>

<template>
  <Head title="My documents - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Services" title="My documents" description="Request official documents. Each is generated from your approved records and carries a verification code." />

    <div class="grid gap-6 lg:grid-cols-[1fr_2fr]">
      <BaseCard title="New request" padding="lg">
        <form class="space-y-4" @submit.prevent="submit">
          <BaseSelect v-model="form.document_type_id" :options="typeOptions" label="Document" :error="form.errors.document_type_id" />
          <p v-if="selectedType?.description" class="text-caption text-muted dark:text-dark-muted">{{ selectedType.description }}</p>
          <div v-if="selectedType?.requires_fee" class="rounded-lg bg-warning/10 p-3 text-caption text-warning-dark dark:text-warning">
            This document incurs an official fee of <strong>${{ Number(selectedType.fee_amount).toFixed(2) }}</strong>. An invoice will be generated upon approval.
          </div>
          <BaseSelect
            v-if="selectedType?.needs_semester"
            v-model="form.semester_id"
            :options="semesterOptions"
            label="Semester"
            placeholder="Choose a semester"
            :error="form.errors.semester_id"
          >
            <template v-if="!semesterOptions.length" #hint>You have no semester with approved grades yet.</template>
          </BaseSelect>
          <BaseTextarea v-model="form.reason" name="reason" label="Purpose (optional)" :rows="3" :error="form.errors.reason" />
          <BaseButton type="submit" :loading="form.processing" :disabled="!typeOptions.length">Submit request</BaseButton>
        </form>
      </BaseCard>

      <BaseCard title="My requests" padding="lg">
        <EmptyState v-if="!requests.data.length" title="No requests yet" description="Your requests and their status appear here." />
        <ul v-else class="divide-y divide-border-default dark:divide-dark-border">
          <li v-for="request in requests.data" :key="request.id" class="py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-small font-medium text-ink dark:text-dark-ink">{{ request.type.name }}<span v-if="request.semester"> · {{ request.semester.name }}</span></p>
                <p class="text-caption text-muted dark:text-dark-muted">Requested {{ formatDate(request.submitted_at) }}<span v-if="request.reason"> · {{ request.reason }}</span></p>
              </div>
              <StatusBadge v-bind="requestBadge(request.status)" />
            </div>
            <p v-if="request.status === 'rejected'" class="mt-2 text-small text-error">Rejected: {{ request.rejection_reason }}</p>
            <div v-if="request.invoice" class="mt-2 flex flex-wrap items-center gap-2 text-caption">
              <span class="font-medium text-ink dark:text-dark-ink">Invoice:</span>
              <span class="font-mono text-primary">{{ request.invoice.invoice_number }}</span>
              <span>(${{ Number(request.invoice.total).toFixed(2) }})</span>
              <span :class="request.invoice.status === 'paid' ? 'text-success font-semibold' : 'text-warning-dark font-semibold'">
                {{ request.invoice.status }}
              </span>
              <a
                v-if="request.invoice.status !== 'paid'"
                href="/my-invoices"
                class="ml-2 rounded bg-primary px-2 py-0.5 text-caption font-medium text-white hover:bg-primary-dark"
              >
                Pay in My Invoices
              </a>
            </div>
            <div v-if="request.document" class="mt-3 flex flex-wrap items-center gap-3">
              <IconButton :icon="Download" :href="`/documents/${request.document.id}/download`" native label="Download PDF" />
              <StatusBadge v-bind="documentBadge(request.document.status)" />
              <span class="text-caption text-muted dark:text-dark-muted">{{ fileSize(request.document.file_size) }} · code <span class="font-mono">{{ request.document.verification_token.slice(0, 12) }}…</span></span>
            </div>
          </li>
        </ul>
        <Pagination :links="requests.meta?.links ?? []" />
      </BaseCard>
    </div>
  </div>
</template>
