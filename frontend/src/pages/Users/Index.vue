<script setup>
import IconButton from '../../components/IconButton.vue'
import { computed, ref, watch } from 'vue'
import { useLiveFilters } from '../../composables/useLiveFilters'
import { Head, router } from '@inertiajs/vue3'
import { FunnelX, Pencil, Plus, Trash2, X } from '@lucide/vue'
import PageHeader from '../../components/PageHeader.vue'
import { useConfirm } from '../../composables/useConfirm'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseTable from '../../components/BaseTable.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  users: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '', role: '', role_id: null }) },
  roles: { type: Array, default: () => [] },
})

const search = ref(props.filters.search ?? '')
const selectedRole = ref(props.filters.role ?? '')

watch(
  () => props.filters,
  (f) => {
    selectedRole.value = f.role ?? ''
  },
)

const activeRoleName = computed(() => {
  if (!selectedRole.value) return ''
  const match = props.roles.find((r) => r.slug === selectedRole.value)
  return match?.name ?? selectedRole.value
})

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'role', label: 'Role' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Created' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const doSearch = (options = {}) => {
  router.get(
    '/users',
    {
      search: search.value || undefined,
      role: selectedRole.value || undefined,
    },
    { preserveState: true, replace: true, ...options },
  )
}

const filterByRole = (slug) => {
  selectedRole.value = slug || ''
  applyNow()
}

const clearFilters = () => {
  search.value = ''
  selectedRole.value = ''
  applyNow()
}

const { confirm } = useConfirm()

const deleteUser = async (user) => {
  if (await confirm({ title: 'Delete user?', message: `Delete user "${user.name}"? This cannot be undone.`, confirmLabel: 'Delete', destructive: true })) router.delete(`/users/${user.id}`)
}
// Soft search: the list follows the filters as they change — typed text after a
// short pause, picked options at once — so there is no search button.
const { applyNow, searching } = useLiveFilters(doSearch, { text: [search], choices: [selectedRole] })
</script>

<template>
  <Head title="Users & roles - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Platform management" title="Users & roles" description="Manage platform accounts, access roles, and account status.">
      <template #actions><IconButton :icon="Plus" href="/users/create" size="md" variant="primary" label="New user" /></template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="applyNow">
        <BaseInput v-model="search" :loading="searching" label="Search accounts" placeholder="Name or email" class="w-full sm:max-w-sm" />
        <div class="w-full sm:w-56">
          <label for="role-filter" class="block text-small font-medium text-ink dark:text-dark-ink">Role</label>
          <select
            id="role-filter"
            v-model="selectedRole"
            class="mt-1.5 block w-full rounded-md border border-border-default bg-surface px-3 py-2 text-small text-ink focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink"
          >
            <option value="">All roles</option>
            <option v-for="r in roles" :key="r.slug || r.id" :value="r.slug">{{ r.name }}</option>
          </select>
        </div>
        <div class="flex gap-1">
          <IconButton v-if="search || selectedRole" :icon="FunnelX" size="md" label="Clear filters" @click="clearFilters" />
        </div>
      </form>

      <!-- Active filter badge -->
      <div v-if="selectedRole" class="mt-3 flex items-center gap-2 border-t border-border-default pt-2.5 text-caption dark:border-dark-border">
        <span class="text-muted dark:text-dark-muted">Filtered by role:</span>
        <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 font-semibold text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
          {{ activeRoleName }}
          <button type="button" class="hover:text-primary-dark" aria-label="Remove role filter" @click="filterByRole('')">
            <X class="h-3 w-3" aria-hidden="true" />
          </button>
        </span>
      </div>
    </BaseCard>

    <BaseTable :columns="columns" :rows="users.data" :row-clickable="false" empty-title="No users found" empty-description="Try changing your search or create a new account.">
      <template #cell-role="{ row }">
        <button
          v-if="row.role?.slug"
          type="button"
          class="rounded px-1.5 py-0.5 text-small font-medium text-primary hover:bg-primary/5 hover:underline dark:text-dark-primary dark:hover:bg-dark-primary/10"
          :title="`Filter by ${row.role?.name}`"
          @click="filterByRole(row.role?.slug)"
        >
          {{ row.role?.name }}
        </button>
        <span v-else class="text-small text-muted dark:text-dark-muted">—</span>
        <p v-if="row.role?.slug === 'department-admin'" class="px-1.5 text-caption text-muted dark:text-dark-muted">{{ row.department ?? 'No department assigned' }}</p>
      </template>
      <template #cell-status="{ row }"><StatusBadge :status="row.is_active ? 'active' : 'inactive'" /></template>
      <template #cell-created_at="{ row }"><span class="text-muted dark:text-dark-muted">{{ row.created_at ? new Date(row.created_at).toLocaleDateString() : '—' }}</span></template>
      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end gap-1">
          <IconButton :icon="Pencil" :href="`/users/${row.id}/edit`" :label="`Edit ${row.name}`" />
          <IconButton :icon="Trash2" variant="danger" :label="`Delete ${row.name}`" @click="deleteUser(row)" />
        </div>
      </template>
    </BaseTable>
    <Pagination :links="users.meta?.links ?? []" />
  </div>
</template>
