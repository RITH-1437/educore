<script setup>
import IconButton from '../../components/IconButton.vue'
import { Download, Search } from '@lucide/vue'
import { exportUrl } from '../../utils/exports'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import { actionLabel, when } from '../../utils/audit'

const props = defineProps({
  logs: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  areas: { type: Array, default: () => [] },
})

const search = ref(props.filters.search ?? '')
const area = ref(props.filters.area ?? '')
const from = ref(props.filters.from ?? '')
const to = ref(props.filters.to ?? '')
const areaOptions = computed(() => [{ value: '', label: 'All areas' }, ...props.areas.map((a) => ({ value: a, label: a.replace('_', ' ') }))])
// Exports what the list currently shows (the applied filters, not unsaved input).
const csvUrl = computed(() => exportUrl('/audit-logs/export', { search: props.filters.search, filters: { area: props.filters.area }, from: props.filters.from, to: props.filters.to }))
const apply = () => router.get('/audit-logs', {
  search: search.value || undefined,
  filters: area.value ? { area: area.value } : undefined,
  from: from.value || undefined,
  to: to.value || undefined,
}, { preserveState: true, replace: true })

const columns = [
  { key: 'when', label: 'When' },
  { key: 'action', label: 'Action' },
  { key: 'actor', label: 'Who' },
  { key: 'target', label: 'Record' },
]
</script>

<template>
  <Head title="Audit logs - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="System" title="Audit logs" description="Append-only record of sign-ins and sensitive changes: who did what, to which record, and when. Entries cannot be edited or deleted. Exports are audited too.">
      <template #actions><IconButton :icon="Download" :href="csvUrl" native size="md" label="Export CSV" /></template>
    </PageHeader>

    <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto] lg:items-end" @submit.prevent="apply">
      <BaseInput v-model="search" name="search" label="Search" placeholder="Description, person or action" />
      <BaseSelect v-model="area" :options="areaOptions" label="Area" />
      <BaseInput v-model="from" name="from" label="From" type="date" />
      <BaseInput v-model="to" name="to" label="To" type="date" />
      <IconButton :icon="Search" type="submit" size="md" label="Apply filters" />
    </form>

    <BaseTable :columns="columns" :rows="logs.data" :row-href="(row) => `/audit-logs/${row.id}`" caption="Audit log entries" empty-title="No entries" empty-description="Sensitive actions appear here as they happen.">
      <template #cell-when="{ row }"><span class="whitespace-nowrap tabular-nums">{{ when(row.created_at) }}</span></template>
      <template #cell-action="{ row }">
        <p class="font-medium">{{ actionLabel(row.action) }}</p>
        <p v-if="row.description" class="max-w-md truncate text-caption text-muted dark:text-dark-muted">{{ row.description }}</p>
      </template>
      <template #cell-actor="{ row }">
        <p>{{ row.actor?.name ?? 'System / removed user' }}</p>
        <p v-if="row.ip_address" class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.ip_address }}</p>
      </template>
      <template #cell-target="{ row }"><span class="text-muted dark:text-dark-muted">{{ row.target ? `${row.target.type} #${row.target.id}` : '—' }}</span></template>
    </BaseTable>

    <Pagination :links="logs.meta?.links ?? []" />
  </div>
</template>
