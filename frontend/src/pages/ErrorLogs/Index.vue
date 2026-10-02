<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { AlertCircle, FileText, Filter, Search, Server, XCircle } from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import PageHeader from '../../components/PageHeader.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  errorLogs: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  summary: { type: Object, required: true },
})

const page = usePage()

const search = ref(props.filters.search ?? '')
const statusCode = ref(props.filters.status_code ?? '')
const statusGroup = ref(props.filters.status_group ?? '')
const method = ref(props.filters.method ?? '')

const columns = [
  { key: 'id', label: 'ID', align: 'right' },
  { key: 'status_code', label: 'Status', sortable: true },
  { key: 'method', label: 'Method', sortable: true },
  { key: 'url', label: 'Path', sortable: true },
  { key: 'exception_class', label: 'Exception' },
  { key: 'user', label: 'User' },
  { key: 'created_at', label: 'Occurred', sortable: true },
]

const statusCodeOptions = [
  { value: '', label: 'All statuses' },
  { value: 404, label: '404 Not Found' },
  { value: 500, label: '500 Internal Server Error' },
  { value: 502, label: '502 Bad Gateway' },
  { value: 503, label: '503 Service Unavailable' },
]

const statusGroupOptions = [
  { value: '', label: 'All groups' },
  { value: 'server', label: 'Server errors (5xx)' },
  { value: 'not_found', label: 'Not found (404)' },
]

const methodOptions = [
  { value: '', label: 'All methods' },
  { value: 'GET', label: 'GET' },
  { value: 'POST', label: 'POST' },
  { value: 'PUT', label: 'PUT' },
  { value: 'PATCH', label: 'PATCH' },
  { value: 'DELETE', label: 'DELETE' },
]

const hasActiveFilters = computed(() =>
  search.value !== '' ||
  statusCode.value !== '' ||
  statusGroup.value !== '' ||
  method.value !== ''
)

const applyFilters = () => {
  const params = {}
  if (search.value) params.search = search.value
  if (statusCode.value) params['filters[status_code]'] = statusCode.value
  if (statusGroup.value) params['filters[status_group]'] = statusGroup.value
  if (method.value) params['filters[method]'] = method.value
  router.get('/error-logs', params, { preserveState: true, replace: true })
}

const clearFilters = () => {
  search.value = ''
  statusCode.value = ''
  statusGroup.value = ''
  method.value = ''
  applyFilters()
}

const formatDate = (dateString) => {
  if (!dateString) return '—'
  return new Date(dateString).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

const getStatusBadgeVariant = (code) => {
  if (code === 404) return 'warning'
  if (code >= 500) return 'error'
  return 'muted'
}
</script>

<template>
  <Head title="System Error Logs - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Diagnostics" title="System error logs" description="Recorded HTTP 404 and 5xx responses. No mutation endpoints — append-only.">
      <template #actions>
        <BaseButton variant="secondary" @click="clearFilters" :disabled="!hasActiveFilters">
          <XCircle class="h-4 w-4" aria-hidden="true" /> Clear filters
        </BaseButton>
      </template>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-3">
      <BaseCard padding="sm">
        <div class="text-center">
          <AlertCircle class="h-12 w-12 mx-auto text-warning dark:text-dark-warning" aria-hidden="true" />
          <p class="mt-2 text-h2 font-semibold text-ink dark:text-dark-ink">{{ summary.total }}</p>
          <p class="text-caption text-muted dark:text-dark-muted">Total recorded</p>
        </div>
      </BaseCard>
      <BaseCard padding="sm">
        <div class="text-center">
          <Server class="h-12 w-12 mx-auto text-error dark:text-dark-error" aria-hidden="true" />
          <p class="mt-2 text-h2 font-semibold text-ink dark:text-dark-ink">{{ summary.server_errors }}</p>
          <p class="text-caption text-muted dark:text-dark-muted">Server errors (5xx)</p>
        </div>
      </BaseCard>
      <BaseCard padding="sm">
        <div class="text-center">
          <FileText class="h-12 w-12 mx-auto text-primary dark:text-dark-primary" aria-hidden="true" />
          <p class="mt-2 text-h2 font-semibold text-ink dark:text-dark-ink">{{ summary.not_found }}</p>
          <p class="text-caption text-muted dark:text-dark-muted">Not found (404)</p>
        </div>
      </BaseCard>
    </div>

    <BaseCard padding="sm">
      <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="applyFilters">
        <BaseInput
          v-model="search"
          label="Search"
          placeholder="Path, message, exception class"
          class="w-full sm:max-w-xs"
        />
        <BaseSelect
          v-model="statusCode"
          :options="statusCodeOptions"
          label="Status code"
          placeholder="All statuses"
          class="w-full sm:w-48"
        />
        <BaseSelect
          v-model="statusGroup"
          :options="statusGroupOptions"
          label="Group"
          placeholder="All groups"
          class="w-full sm:w-52"
        />
        <BaseSelect
          v-model="method"
          :options="methodOptions"
          label="Method"
          placeholder="All methods"
          class="w-full sm:w-40"
        />
        <BaseButton type="submit" variant="secondary">
          <Search class="h-4 w-4" aria-hidden="true" /> Apply
        </BaseButton>
      </form>
    </BaseCard>

    <BaseTable
      :columns="columns"
      :rows="errorLogs.data"
      empty-title="No errors recorded"
      empty-description="Errors are captured automatically from 404 and 5xx responses."
    >
      <template #cell-id="{ row }">
        <span class="font-mono text-muted dark:text-dark-muted">#{{ row.id }}</span>
      </template>
      <template #cell-status_code="{ row }">
        <BaseBadge :variant="getStatusBadgeVariant(row.status_code)" dot>
          {{ row.status_code }}
        </BaseBadge>
      </template>
      <template #cell-method="{ row }">
        <BaseBadge variant="muted" size="sm">
          {{ row.method }}
        </BaseBadge>
      </template>
      <template #cell-url="{ row }">
        <Link
          :href="`/error-logs/${row.id}`"
          class="font-mono text-small text-primary hover:underline dark:text-dark-primary"
        >
          {{ row.url }}
        </Link>
      </template>
      <template #cell-exception_class="{ row }">
        <span v-if="row.exception_class" class="font-mono text-small text-muted dark:text-dark-muted">
          {{ row.exception_class }}
        </span>
        <span v-else class="text-muted dark:text-dark-muted">—</span>
      </template>
      <template #cell-user="{ row }">
        <span v-if="row.user" class="text-small text-ink dark:text-dark-ink">
          {{ row.user.name }} ({{ row.user.email }})
        </span>
        <span v-else class="text-muted dark:text-dark-muted">Anonymous</span>
      </template>
      <template #cell-created_at="{ row }">
        <span class="font-mono text-small text-ink dark:text-dark-ink">
          {{ formatDate(row.created_at) }}
        </span>
      </template>
    </BaseTable>

    <Pagination :links="errorLogs.meta?.links ?? []" />
  </div>
</template>