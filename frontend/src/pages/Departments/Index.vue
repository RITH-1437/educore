<script setup>
import IconButton from '../../components/IconButton.vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Archive, ArchiveRestore, Pencil, Plus, Search, Trash2 } from '@lucide/vue'
import BaseButton from '../../components/BaseButton.vue'
import PageHeader from '../../components/PageHeader.vue'
import { useConfirm } from '../../composables/useConfirm'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import BaseTextarea from '../../components/BaseTextarea.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'

const props = defineProps({
  departments: { type: Object, required: true },
  universities: { type: Array, required: true },
  filters: { type: Object, default: () => ({ search: '', university_id: null, is_active: null }) },
})

const page = usePage()

// A Department Admin may read the structure but not change it, so every write
// control is hidden rather than left to fail with a 403
// (`skills/faculty-department/SKILL.md` §8).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))

const search = ref(props.filters.search ?? '')
const universityId = ref(props.filters.university_id ?? '')
const isActive = ref(props.filters.is_active === null ? '' : String(props.filters.is_active))

const form = useForm({ university_id: '', code: '', name: '', head_name: '', description: '' })
const showModal = ref(false)

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'Department' },
  { key: 'head_name', label: 'Head' },
  { key: 'programs_count', label: 'Programs', align: 'center' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

// The current university comes first, so it is the form's default.
const universityOptions = computed(() =>
  props.universities.map((university) => ({ value: university.id, label: `${university.code} — ${university.name}` })),
)
const showUniversity = computed(() => props.universities.length > 1)

const activeFilterOptions = [
  { value: '', label: 'All states' },
  { value: 'true', label: 'Active' },
  { value: 'false', label: 'Archived' },
]

const applyFilters = () =>
  router.get(
    '/departments',
    {
      search: search.value || undefined,
      filters: {
        university_id: universityId.value || undefined,
        is_active: isActive.value === '' ? undefined : isActive.value,
      },
    },
    { preserveState: true, replace: true },
  )

const openModal = () => {
  form.defaults({ university_id: universityId.value || universityOptions.value[0]?.value || '', code: '', name: '', head_name: '', description: '' })
  form.reset()
  form.clearErrors()
  showModal.value = true
}

const submit = () => form.post('/departments', { preserveScroll: true, onSuccess: () => (showModal.value = false) })

const { confirm } = useConfirm()

const archive = async (department) => {
  if (await confirm({ title: 'Archive department?', message: `Archive "${department.name}"? It stays visible but inactive; its programs, courses and lecturers keep their department.`, confirmLabel: 'Archive' })) {
    router.post(`/departments/${department.id}/archive`)
  }
}

const reactivate = (department) => router.post(`/departments/${department.id}/reactivate`)

const destroy = async (department) => {
  if (await confirm({ title: 'Delete department?', message: `Delete "${department.name}"? This is refused while programs, courses or lecturers reference it.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/departments/${department.id}`)
  }
}
</script>

<template>
  <Head title="Departments - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="University structure" title="Departments" description="The academic units of the university. Programs, courses and lecturers belong to a department; departments are archived rather than deleted while anything references them.">
      <template v-if="canManage" #actions>
        <IconButton :icon="Plus" size="md" variant="primary" label="New department" @click="openModal" />
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="flex flex-col gap-3 lg:flex-row lg:items-end" @submit.prevent="applyFilters">
        <BaseInput v-model="search" label="Search departments" placeholder="Code or name" class="w-full lg:max-w-xs" />
        <BaseSelect v-if="showUniversity" v-model="universityId" label="University" :options="universityOptions" placeholder="All universities" class="w-full lg:max-w-xs" />
        <BaseSelect v-model="isActive" label="State" :options="activeFilterOptions" />
        <IconButton :icon="Search" type="submit" size="md" label="Search departments" />
      </form>
    </BaseCard>

    <div class="space-y-4">
      <BaseTable
        :columns="columns"
        :rows="departments.data"
        caption="Departments"
        empty-title="No departments found"
        :empty-description="canManage ? 'Create a department to start building the academic structure.' : 'No department is visible to you yet.'"
      >
        <template #cell-code="{ row }">
          <span class="font-semibold text-ink dark:text-dark-ink">{{ row.code }}</span>
        </template>
        <template #cell-name="{ row }">
          <p class="font-medium text-ink dark:text-dark-ink">{{ row.name }}</p>
          <p v-if="showUniversity && row.university" class="text-caption text-muted dark:text-dark-muted">{{ row.university.code }}</p>
        </template>
        <template #cell-head_name="{ row }">
          <span class="text-muted dark:text-dark-muted">{{ row.head_name ?? '—' }}</span>
        </template>
        <template #cell-programs_count="{ row }">
          <span class="tabular-nums">{{ row.programs_count ?? 0 }}</span>
        </template>
        <template #cell-is_active="{ row }">
          <StatusBadge :status="row.is_active ? 'active' : 'archived'" />
        </template>
        <template #cell-actions="{ row }">
          <div v-if="canManage" class="flex items-center justify-end gap-1">
            <IconButton :icon="Pencil" :href="`/departments/${row.id}/edit`" :label="`Edit ${row.name}`" />
            <IconButton v-if="row.is_active" :icon="Archive" :label="`Archive ${row.name}`" @click="archive(row)" />
            <IconButton v-else :icon="ArchiveRestore" variant="success" :label="`Reactivate ${row.name}`" @click="reactivate(row)" />
            <IconButton :icon="Trash2" variant="danger" :label="`Delete ${row.name}`" @click="destroy(row)" />
          </div>
          <span v-else class="block text-right text-caption text-muted dark:text-dark-muted">Read only</span>
        </template>
      </BaseTable>

      <Pagination :links="departments.meta?.links ?? []" />
    </div>

    <BaseModal v-model="showModal" title="New department" size="md">
      <form id="department-form" class="space-y-5" @submit.prevent="submit">
        <BaseSelect
          v-if="showUniversity || form.errors.university_id"
          v-model="form.university_id"
          label="University"
          :options="universityOptions"
          :error="form.errors.university_id"
          required
        />
        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="form.code" label="Code" placeholder="CSE" :error="form.errors.code" required />
          <BaseInput v-model="form.name" label="Name" placeholder="Department of Computer Science" :error="form.errors.name" required />
        </div>
        <BaseInput v-model="form.head_name" label="Head" placeholder="Dr. Dara Lim" :error="form.errors.head_name" />
        <BaseTextarea v-model="form.description" label="Description" rows="3" :error="form.errors.description" />
        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="form.processing">Create department</BaseButton>
          <BaseButton variant="ghost" @click="showModal = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
