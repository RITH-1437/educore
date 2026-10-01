<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Search, UserPlus } from '@lucide/vue'
import BaseButton from '../../components/BaseButton.vue'
import PageHeader from '../../components/PageHeader.vue'
import { useConfirm } from '../../composables/useConfirm'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseTable from '../../components/BaseTable.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  users: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '' }) },
})

const search = ref(props.filters.search ?? '')
const columns = [
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'role', label: 'Role' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Created' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const doSearch = () => router.get('/users', { search: search.value || undefined }, { preserveState: true, replace: true })
const { confirm } = useConfirm()

const deleteUser = async (user) => {
  if (await confirm({ title: 'Delete user?', message: `Delete user "${user.name}"? This cannot be undone.`, confirmLabel: 'Delete', destructive: true })) router.delete(`/users/${user.id}`)
}
</script>

<template>
  <Head title="Users & roles - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Platform management" title="Users & roles" description="Manage platform accounts, access roles, and account status.">
      <template #actions><BaseButton href="/users/create"><UserPlus class="h-4 w-4" aria-hidden="true" /> New user</BaseButton></template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="doSearch">
        <BaseInput v-model="search" label="Search accounts" placeholder="Name or email" class="w-full sm:max-w-sm" />
        <BaseButton type="submit" variant="secondary"><Search class="h-4 w-4" aria-hidden="true" /> Search</BaseButton>
      </form>
    </BaseCard>

    <BaseTable :columns="columns" :rows="users.data" :row-clickable="false" empty-title="No users found" empty-description="Try changing your search or create a new account.">
      <template #cell-role="{ row }"><span class="text-small text-muted dark:text-dark-muted">{{ row.role?.name ?? '—' }}</span></template>
      <template #cell-status="{ row }"><StatusBadge :status="row.is_active ? 'active' : 'inactive'" /></template>
      <template #cell-created_at="{ row }"><span class="text-muted dark:text-dark-muted">{{ row.created_at ? new Date(row.created_at).toLocaleDateString() : '—' }}</span></template>
      <template #cell-actions="{ row }">
        <div class="flex justify-end gap-3">
          <Link :href="`/users/${row.id}/edit`" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary">Edit</Link>
          <button type="button" class="text-small font-semibold text-error hover:underline" @click="deleteUser(row)">Delete</button>
        </div>
      </template>
    </BaseTable>
    <Pagination :links="users.links" />
  </div>
</template>
