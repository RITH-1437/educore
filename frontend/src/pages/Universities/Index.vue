<script setup>
import IconButton from '../../components/IconButton.vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { FunnelX, Pencil, Plus, School, Search, Star, Trash2, X } from '@lucide/vue'
import BaseBadge from '../../components/BaseBadge.vue'
import BaseButton from '../../components/BaseButton.vue'
import PageHeader from '../../components/PageHeader.vue'
import { useConfirm } from '../../composables/useConfirm'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseTable from '../../components/BaseTable.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import Pagination from '../../components/Pagination.vue'

const props = defineProps({
  universities: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '', status: '' }) },
})

const page = usePage()

// Faculty Admin may read the structure but not change it, so every write
// control is hidden rather than left to fail with a 403
// (`skills/faculty-department/SKILL.md` §8).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')

const STATUS_OPTIONS = [
  { value: '', label: 'All states' },
  { value: 'current', label: 'Current' },
  { value: 'other', label: 'Other' },
]

watch(
  () => props.filters,
  (f) => {
    search.value = f.search ?? ''
    status.value = f.status ?? ''
  },
)

const showCreate = ref(false)

const createForm = useForm({
  code: '',
  name: '',
  short_name: '',
  address: '',
  phone: '',
  email: '',
  website: '',
})

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'University' },
  { key: 'short_name', label: 'Short name' },
  { key: 'faculties_count', label: 'Faculties', align: 'center' },
  { key: 'is_current', label: 'State' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const isEmpty = computed(() => (props.universities?.data ?? []).length === 0)

const applyFilters = () =>
  router.get(
    '/universities',
    {
      search: search.value || undefined,
      status: status.value || undefined,
    },
    { preserveState: true, replace: true },
  )

const clearFilters = () => {
  search.value = ''
  status.value = ''
  router.get('/universities', {}, { preserveState: true, replace: true })
}

const filterByStatus = (val) => {
  status.value = val
  applyFilters()
}

const openCreate = () => {
  createForm.reset()
  showCreate.value = true
}

const submitCreate = () =>
  createForm.post('/universities', {
    preserveScroll: true,
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
    },
  })

const makeCurrent = (university) => router.post(`/universities/${university.id}/current`)

const { confirm } = useConfirm()

const destroy = async (university) => {
  if (await confirm({ title: 'Delete university?', message: `Delete "${university.name}"? This is refused while it still has faculties.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/universities/${university.id}`)
  }
}
</script>

<template>
  <Head title="University - EduCore" />
  <div class="space-y-6">
    <PageHeader title="University" description="The single institution record that owns every faculty, program and course.">
      <template #actions>
        <IconButton :icon="School" href="/faculties" size="md" label="Manage faculties" />
        <IconButton v-if="canManage" :icon="Plus" size="md" variant="primary" label="New university" @click="openCreate" />
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="applyFilters">
        <BaseInput v-model="search" label="Search" placeholder="Code or name" class="w-full sm:max-w-xs" />
        <div class="w-full sm:w-44">
          <label for="state-filter" class="block text-small font-medium text-ink dark:text-dark-ink">State</label>
          <select
            id="state-filter"
            v-model="status"
            class="mt-1.5 block w-full rounded-md border border-border-default bg-surface px-3 py-2 text-small text-ink focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-dark-border dark:bg-dark-surface dark:text-dark-ink"
            @change="applyFilters"
          >
            <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
        <div class="flex gap-1">
          <IconButton :icon="Search" type="submit" size="md" label="Search universities" />
          <IconButton v-if="search || status" :icon="FunnelX" size="md" label="Clear filters" @click="clearFilters" />
        </div>
      </form>

      <!-- Active filter badge -->
      <div v-if="status" class="mt-3 flex items-center gap-2 border-t border-border-default pt-2.5 text-caption dark:border-dark-border">
        <span class="text-muted dark:text-dark-muted">Filtered by state:</span>
        <span class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-0.5 font-semibold text-primary dark:bg-dark-primary/15 dark:text-dark-primary">
          {{ status === 'current' ? 'Current' : 'Other' }}
          <button type="button" class="hover:text-primary-dark" aria-label="Remove filter" @click="filterByStatus('')">
            <X class="h-3 w-3" aria-hidden="true" />
          </button>
        </span>
      </div>
    </BaseCard>

    <BaseTable
      :columns="columns"
      :rows="universities.data"
      empty-title="No universities found"
      empty-description="Create the institution record to start building the academic structure."
    >
      <template #empty-action>
        <IconButton v-if="isEmpty && !search && canManage" :icon="Plus" size="md" variant="primary" label="New university" @click="openCreate" />
      </template>
      <template #cell-code="{ row }">
        <span class="font-semibold text-ink dark:text-dark-ink">{{ row.code }}</span>
      </template>
      <template #cell-name="{ row }">
        <div>
          <p class="font-medium text-ink dark:text-dark-ink">{{ row.name }}</p>
          <p v-if="row.email" class="text-caption text-muted dark:text-dark-muted">{{ row.email }}</p>
        </div>
      </template>
      <template #cell-short_name="{ row }">
        <span class="text-muted dark:text-dark-muted">{{ row.short_name ?? '—' }}</span>
      </template>
      <template #cell-faculties_count="{ row }">
        <span class="font-semibold text-ink dark:text-dark-ink">{{ row.faculties_count ?? 0 }}</span>
      </template>
      <template #cell-is_current="{ row }">
        <button
          type="button"
          class="transition-opacity hover:opacity-80"
          :title="`Filter by ${row.is_current ? 'Current' : 'Other'}`"
          @click="filterByStatus(row.is_current ? 'current' : 'other')"
        >
          <BaseBadge :variant="row.is_current ? 'success' : 'muted'" :dot="row.is_current">
            {{ row.is_current ? 'Current' : 'Other' }}
          </BaseBadge>
        </button>
      </template>
      <template #cell-actions="{ row }">
        <div v-if="canManage" class="flex items-center justify-end gap-1">
          <IconButton v-if="!row.is_current" :icon="Star" variant="success" :label="`Make ${row.name} the current university`" @click="makeCurrent(row)" />
          <IconButton :icon="Pencil" :href="`/universities/${row.id}/edit`" :label="`Edit ${row.name}`" />
          <IconButton :icon="Trash2" variant="danger" :label="`Delete ${row.name}`" @click="destroy(row)" />
        </div>
        <span v-else class="block text-right text-caption text-muted dark:text-dark-muted">Read only</span>
      </template>
    </BaseTable>

    <Pagination :links="universities.meta?.links ?? []" />

    <BaseModal v-model="showCreate" title="New university" size="md">
      <form class="space-y-5" @submit.prevent="submitCreate">
        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="createForm.code" label="Code" placeholder="ITC" :error="createForm.errors.code" required />
          <BaseInput v-model="createForm.short_name" label="Short name" placeholder="ITC" :error="createForm.errors.short_name" />
        </div>
        <BaseInput v-model="createForm.name" label="Name" placeholder="Institute of Technology Cambodia" :error="createForm.errors.name" required />
        <BaseTextarea v-model="createForm.address" label="Address" rows="3" :error="createForm.errors.address" />
        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="createForm.phone" label="Phone" placeholder="+855 23 883 222" :error="createForm.errors.phone" />
          <BaseInput v-model="createForm.email" label="Email" type="email" placeholder="info@educore.kh" :error="createForm.errors.email" />
        </div>
        <BaseInput v-model="createForm.website" label="Website" type="url" placeholder="https://www.educore.kh" :error="createForm.errors.website" />

        <ErrorAlert
          v-if="Object.keys(createForm.errors).length"
          title="Check the form"
          message="Correct the highlighted fields and try again."
        />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="createForm.processing">Create university</BaseButton>
          <BaseButton variant="ghost" @click="showCreate = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>