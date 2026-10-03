<script setup>
import IconButton from '../../components/IconButton.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { CircleCheckBig, CirclePlay, Pencil, Plus, Search, Star, Trash2 } from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  academicYears: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '', status: '' }) },
})

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')

const STATUSES = [
  { value: '', label: 'All statuses' },
  { value: 'planned', label: 'Planned' },
  { value: 'active', label: 'Active' },
  { value: 'completed', label: 'Completed' },
]

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'Name' },
  { key: 'span', label: 'Span' },
  { key: 'semesters_count', label: 'Semesters' },
  { key: 'status', label: 'Status' },
  { key: 'is_current', label: 'Current' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const applyFilters = () =>
  router.get(
    '/academic-years',
    { search: search.value || undefined, filters: status.value ? { status: status.value } : undefined },
    { preserveState: true, replace: true },
  )

const changeStatus = (academicYear, nextStatus) => {
  router.post(`/academic-years/${academicYear.id}/status`, { status: nextStatus })
}

const makeCurrent = (academicYear) => {
  router.post(`/academic-years/${academicYear.id}/current`)
}

const { confirm } = useConfirm()

const deleteYear = async (academicYear) => {
  if (await confirm({ title: 'Delete academic year?', message: `Delete academic year "${academicYear.code}"? This cannot be undone.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/academic-years/${academicYear.id}`)
  }
}
</script>

<template>
  <Head title="Academic years" />
  <div class="space-y-6">
    <PageHeader eyebrow="Platform management" title="Academic years" description="The university calendar every course offering hangs from.">
      <template #actions>
        <IconButton :icon="Plus" href="/academic-years/create" size="md" variant="primary" label="New academic year" />
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="applyFilters">
        <BaseInput v-model="search" type="search" label="Search" placeholder="Code or name" class="w-full sm:max-w-xs" />
        <BaseSelect v-model="status" label="Status" :options="STATUSES" @change="applyFilters" />
        <IconButton :icon="Search" type="submit" size="md" label="Apply filters" />
      </form>
    </BaseCard>

    <BaseTable :columns="columns" :rows="academicYears.data" caption="Academic years" empty-title="No academic years found" empty-description="Create an academic year or adjust your filters.">
      <template #cell-code="{ row }"><span class="font-medium">{{ row.code }}</span></template>
      <template #cell-span="{ row }"><span class="whitespace-nowrap text-muted dark:text-dark-muted">{{ row.start_date }} → {{ row.end_date }}</span></template>
      <template #cell-semesters_count="{ row }">{{ row.semesters_count ?? 0 }}</template>
      <template #cell-status="{ row }"><StatusBadge :status="row.status" :label="row.status_label" /></template>
      <template #cell-is_current="{ row }">
        <BaseBadge v-if="row.is_current" variant="primary">Current</BaseBadge>
        <span v-else class="text-muted dark:text-dark-muted" aria-label="Not current">—</span>
      </template>
      <template #cell-actions="{ row }">
        <div class="flex flex-wrap items-center justify-end gap-1">
          <IconButton :icon="Pencil" :href="`/academic-years/${row.id}/edit`" :label="`Edit ${row.code}`" />
          <IconButton v-if="row.status === 'planned'" :icon="CirclePlay" variant="success" :label="`Activate ${row.code}`" @click="changeStatus(row, 'active')" />
          <IconButton v-else-if="row.status === 'active'" :icon="CircleCheckBig" variant="success" :label="`Complete ${row.code}`" @click="changeStatus(row, 'completed')" />
          <IconButton v-if="row.status === 'active' && !row.is_current" :icon="Star" :label="`Make ${row.code} the current year`" @click="makeCurrent(row)" />
          <IconButton :icon="Trash2" variant="danger" :label="`Delete ${row.code}`" @click="deleteYear(row)" />
        </div>
      </template>
    </BaseTable>
    <Pagination :links="academicYears.meta?.links ?? []" />
  </div>
</template>
