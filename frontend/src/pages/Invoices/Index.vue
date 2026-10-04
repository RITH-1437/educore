<script setup>
import IconButton from '../../components/IconButton.vue'
import { Download, Plus, Search } from '@lucide/vue'
import { exportUrl } from '../../utils/exports'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { invoiceBadge, money } from '../../utils/finance'

const props = defineProps({
  invoices: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  statuses: { type: Array, default: () => [] },
})

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const statusOptions = computed(() => [{ value: '', label: 'All statuses' }, ...props.statuses.map((value) => ({ value, label: invoiceBadge(value).label }))])
// Exports what the list currently shows (the applied filters, not unsaved input).
const csvUrl = computed(() => exportUrl('/invoices/export', { search: props.filters.search, filters: { status: props.filters.status } }))
const apply = () => router.get('/invoices', { search: search.value || undefined, filters: status.value ? { status: status.value } : undefined }, { preserveState: true, replace: true })

const columns = [
  { key: 'number', label: 'Invoice' },
  { key: 'student', label: 'Student' },
  { key: 'due', label: 'Due' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'balance', label: 'Balance', align: 'right' },
  { key: 'status', label: 'Status' },
]
</script>

<template>
  <Head title="Invoices - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Operations" title="Invoices" description="Charges and payment records per student. Payments are recorded by staff; there is no online payment.">
      <template #actions>
        <IconButton :icon="Download" :href="csvUrl" native size="md" label="Export CSV" />
        <IconButton :icon="Plus" href="/invoices/create" size="md" variant="primary" label="New invoice" />
      </template>
    </PageHeader>

    <form class="grid gap-3 sm:grid-cols-[2fr_1fr_auto] sm:items-end" @submit.prevent="apply">
      <BaseInput v-model="search" name="search" label="Search" placeholder="Invoice number, title, student…" />
      <BaseSelect v-model="status" :options="statusOptions" label="Status" />
      <IconButton :icon="Search" type="submit" size="md" label="Apply filters" />
    </form>

    <BaseTable :columns="columns" :rows="invoices.data" :row-href="(row) => `/invoices/${row.id}`" caption="Invoices" empty-title="No invoices" empty-description="Create an invoice to bill a student.">
      <template #cell-number="{ row }">
        <p class="font-mono font-medium">{{ row.invoice_number }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.title }}</p>
      </template>
      <template #cell-student="{ row }">
        <p>{{ row.student.full_name }}</p>
        <p class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student.student_number }}</p>
      </template>
      <template #cell-due="{ row }">{{ row.due_date }}</template>
      <template #cell-total="{ row }"><span class="tabular-nums">{{ money(row.total, row.currency) }}</span></template>
      <template #cell-balance="{ row }"><span class="font-semibold tabular-nums">{{ money(row.status === 'cancelled' ? 0 : row.balance, row.currency) }}</span></template>
      <template #cell-status="{ row }"><StatusBadge v-bind="invoiceBadge(row.status)" /></template>
    </BaseTable>

    <Pagination :links="invoices.meta?.links ?? []" />
  </div>
</template>
