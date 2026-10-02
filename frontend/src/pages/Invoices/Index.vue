<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseButton from '../../components/BaseButton.vue'
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
const apply = () => router.get('/invoices', { search: search.value || undefined, filters: status.value ? { status: status.value } : undefined }, { preserveState: true, replace: true })

const columns = [
  { key: 'number', label: 'Invoice' },
  { key: 'student', label: 'Student' },
  { key: 'due', label: 'Due' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'balance', label: 'Balance', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]
</script>

<template>
  <Head title="Invoices - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Operations" title="Invoices" description="Charges and payment records per student. Payments are recorded by staff; there is no online payment.">
      <template #actions>
        <BaseButton href="/invoices/create">New invoice</BaseButton>
      </template>
    </PageHeader>

    <form class="grid gap-3 sm:grid-cols-[2fr_1fr_auto] sm:items-end" @submit.prevent="apply">
      <BaseInput v-model="search" name="search" label="Search" placeholder="Invoice number, title, student…" />
      <BaseSelect v-model="status" :options="statusOptions" label="Status" />
      <BaseButton type="submit" variant="secondary">Filter</BaseButton>
    </form>

    <BaseTable :columns="columns" :rows="invoices.data" caption="Invoices" empty-title="No invoices" empty-description="Create an invoice to bill a student.">
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
      <template #cell-actions="{ row }">
        <BaseButton :href="`/invoices/${row.id}`" size="sm" variant="secondary">Open</BaseButton>
      </template>
    </BaseTable>

    <Pagination :links="invoices.meta?.links ?? []" />
  </div>
</template>
