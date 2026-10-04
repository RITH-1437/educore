<script setup>
import { Head } from '@inertiajs/vue3'
import { AlertTriangle, Receipt, Wallet } from '@lucide/vue'
import BaseTable from '../../components/BaseTable.vue'
import PageHeader from '../../components/PageHeader.vue'
import StatCard from '../../components/StatCard.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { invoiceBadge, money } from '../../utils/finance'

defineProps({
  data: { type: Array, default: () => [] },
  summary: { type: Array, default: () => [] },
})

const columns = [
  { key: 'number', label: 'Invoice' },
  { key: 'due', label: 'Due' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'balance', label: 'Balance', align: 'right' },
  { key: 'status', label: 'Status' },
]
</script>

<template>
  <Head title="My invoices - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Services" title="My invoices" description="Your charges and the payments recorded by the finance office. Pay at the university; there is no online payment." />

    <!-- One row of totals per currency: amounts in different currencies are never added. -->
    <div v-for="row in summary" :key="row.currency" class="grid gap-4 sm:grid-cols-3">
      <StatCard :label="`Balance due (${row.currency})`" :value="money(row.balance, row.currency)" :icon="Wallet" :tone="row.balance > 0 ? 'warning' : 'success'" :detail="`${row.count} invoice${row.count === 1 ? '' : 's'}`" />
      <StatCard :label="`Paid (${row.currency})`" :value="money(row.paid, row.currency)" :icon="Receipt" tone="success" :detail="`of ${money(row.invoiced, row.currency)} invoiced`" />
      <StatCard :label="`Overdue (${row.currency})`" :value="money(row.overdue, row.currency)" :icon="AlertTriangle" :tone="row.overdue > 0 ? 'warning' : 'muted'" />
    </div>

    <BaseTable :columns="columns" :rows="data" :row-href="(row) => `/invoices/${row.id}`" caption="My invoices" empty-title="No invoices" empty-description="Invoices issued to you appear here.">
      <template #cell-number="{ row }">
        <p class="font-mono font-medium">{{ row.invoice_number }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.title }}</p>
      </template>
      <template #cell-due="{ row }">{{ row.due_date }}</template>
      <template #cell-total="{ row }"><span class="tabular-nums">{{ money(row.total, row.currency) }}</span></template>
      <template #cell-balance="{ row }"><span class="font-semibold tabular-nums">{{ money(row.status === 'cancelled' ? 0 : row.balance, row.currency) }}</span></template>
      <template #cell-status="{ row }"><StatusBadge v-bind="invoiceBadge(row.status)" /></template>
    </BaseTable>
  </div>
</template>
