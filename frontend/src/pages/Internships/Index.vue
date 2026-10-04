<script setup>
import IconButton from '../../components/IconButton.vue'
import { Building2 } from '@lucide/vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { formatDate, statusBadge } from '../../utils/internships'

const props = defineProps({
  internships: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  statuses: { type: Array, default: () => [] },
})

const status = ref(props.filters.status ?? '')
const statusOptions = computed(() => [{ value: '', label: 'All statuses' }, ...props.statuses.map((s) => ({ value: s, label: statusBadge(s).label }))])
const apply = () => router.get('/internships', { filters: status.value ? { status: status.value } : undefined }, { preserveState: true, replace: true })

const columns = [
  { key: 'student', label: 'Student' },
  { key: 'position', label: 'Position' },
  { key: 'dates', label: 'Dates' },
  { key: 'status', label: 'Status' },
]
</script>

<template>
  <Head title="Internships - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Operations" title="Internships" description="Applications waiting for review come first.">
      <template #actions>
        <IconButton :icon="Building2" href="/internship-companies" size="md" label="Companies" />
      </template>
    </PageHeader>

    <div class="max-w-xs">
      <BaseSelect v-model="status" :options="statusOptions" label="Status" @update:model-value="apply" />
    </div>

    <BaseTable :columns="columns" :rows="internships.data" :row-href="(row) => `/internships/${row.id}`" caption="Internships" empty-title="No internships" empty-description="Applications from students appear here.">
      <template #cell-student="{ row }">
        <p class="font-medium">{{ row.student.full_name }}</p>
        <p class="font-mono text-caption text-muted dark:text-dark-muted">{{ row.student.student_number }}</p>
      </template>
      <template #cell-position="{ row }">
        <p>{{ row.position_title }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.company.name }}</p>
      </template>
      <template #cell-dates="{ row }">{{ formatDate(row.start_date) }} – {{ formatDate(row.end_date) }}</template>
      <template #cell-status="{ row }"><StatusBadge v-bind="statusBadge(row.status)" /></template>
    </BaseTable>

    <Pagination :links="internships.meta?.links ?? []" />
  </div>
</template>
