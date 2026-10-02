<script setup>
import IconButton from '../../components/IconButton.vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue'
import BaseButton from '../../components/BaseButton.vue'
import BaseCard from '../../components/BaseCard.vue'
import BaseInput from '../../components/BaseInput.vue'
import BaseModal from '../../components/BaseModal.vue'
import BaseSelect from '../../components/BaseSelect.vue'
import BaseTable from '../../components/BaseTable.vue'
import ErrorAlert from '../../components/ErrorAlert.vue'
import PageHeader from '../../components/PageHeader.vue'
import Pagination from '../../components/Pagination.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import ProgramForm from '../../components/programs/ProgramForm.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  programs: { type: Object, required: true },
  faculties: { type: Object, required: true },
  departments: { type: Object, required: true },
  degreeLevels: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const { confirm } = useConfirm()

// Faculty Admin may read programs but not change them; write controls are
// hidden rather than left to fail with a 403 (`skills/program-management/SKILL.md` §8).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))

const faculties = computed(() => props.faculties?.data ?? [])
const departments = computed(() => props.departments?.data ?? [])

const search = ref(props.filters.search ?? '')
const facultyId = ref(props.filters.faculty_id ?? '')
const degreeLevel = ref(props.filters.degree_level ?? '')
const activeState = ref(props.filters.is_active === null || props.filters.is_active === undefined ? '' : props.filters.is_active ? '1' : '0')

const facultyOptions = computed(() => faculties.value.map((faculty) => ({ value: faculty.id, label: faculty.name })))
const levelOptions = computed(() => props.degreeLevels.map((level) => ({ value: level, label: level.charAt(0).toUpperCase() + level.slice(1) })))
const stateOptions = [
  { value: '1', label: 'Active' },
  { value: '0', label: 'Archived' },
]

const hasFilters = computed(() => Boolean(search.value || facultyId.value || degreeLevel.value || activeState.value !== ''))

const applyFilters = () => {
  const filters = {
    faculty_id: facultyId.value || undefined,
    degree_level: degreeLevel.value || undefined,
    is_active: activeState.value === '' ? undefined : activeState.value,
  }
  router.get(
    '/programs',
    { search: search.value || undefined, filters: Object.values(filters).some((v) => v !== undefined) ? filters : undefined },
    { preserveState: true, replace: true },
  )
}

const clearFilters = () => {
  search.value = ''
  facultyId.value = ''
  degreeLevel.value = ''
  activeState.value = ''
  router.get('/programs', {}, { preserveState: true, replace: true })
}

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'Program' },
  { key: 'degree_level', label: 'Level' },
  { key: 'duration_years', label: 'Duration' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const showCreate = ref(false)
const createForm = useForm({
  department_id: '',
  code: '',
  name: '',
  degree_level: 'bachelor',
  duration_years: '',
  credits_required: '',
})

const openCreate = () => {
  createForm.reset()
  createForm.clearErrors()
  showCreate.value = true
}

const submitCreate = () =>
  createForm.post('/programs', {
    preserveScroll: true,
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
    },
  })

const archive = async (program) => {
  if (await confirm({ title: 'Archive program?', message: `Archive "${program.name}"? It stays visible but inactive; students and curriculum keep their reference.`, confirmLabel: 'Archive' })) {
    router.post(`/programs/${program.id}/archive`, {}, { preserveScroll: true })
  }
}

const reactivate = (program) => router.post(`/programs/${program.id}/reactivate`, {}, { preserveScroll: true })

const destroy = async (program) => {
  if (await confirm({ title: 'Delete program?', message: `Delete "${program.name}"? This is refused while students or curriculum courses reference it — archive it instead.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/programs/${program.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <Head title="Programs - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Academic structure" title="Programs" description="Degree tracks offered by each department. A program belongs to exactly one department and can be archived instead of deleted.">
      <template v-if="canManage" #actions>
        <BaseButton @click="openCreate"><Plus class="h-4 w-4" aria-hidden="true" /> New program</BaseButton>
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_2fr_1fr_1fr_auto] lg:items-end" @submit.prevent="applyFilters">
        <BaseInput v-model="search" label="Search" placeholder="Code or name" />
        <BaseSelect v-model="facultyId" label="Faculty" :options="facultyOptions" placeholder="All faculties" />
        <BaseSelect v-model="degreeLevel" label="Level" :options="levelOptions" placeholder="All levels" />
        <BaseSelect v-model="activeState" label="Status" :options="stateOptions" placeholder="All" />
        <div class="flex gap-2">
          <BaseButton type="submit" variant="secondary"><Search class="h-4 w-4" aria-hidden="true" /> Filter</BaseButton>
          <BaseButton v-if="hasFilters" variant="ghost" @click="clearFilters">Clear</BaseButton>
        </div>
      </form>
    </BaseCard>

    <BaseTable
      :columns="columns"
      :rows="programs.data"
      caption="Programs"
      :empty-title="hasFilters ? 'No programs match your filters' : 'No programs yet'"
      :empty-description="hasFilters ? 'Try different filters or clear them.' : 'Create the first program under a department.'"
    >
      <template #empty-action>
        <BaseButton v-if="!hasFilters && canManage" @click="openCreate">+ New program</BaseButton>
      </template>
      <template #cell-code="{ row }"><span class="font-semibold">{{ row.code }}</span></template>
      <template #cell-name="{ row }">
        <p class="font-medium">{{ row.name }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.department?.name }}<template v-if="row.department?.faculty"> · {{ row.department.faculty.code }}</template></p>
      </template>
      <template #cell-degree_level="{ row }"><span class="capitalize">{{ row.degree_level }}</span></template>
      <template #cell-duration_years="{ row }">
        <span class="whitespace-nowrap text-muted dark:text-dark-muted">
          <template v-if="row.duration_years">{{ row.duration_years }} yr</template><template v-else>—</template>
          <template v-if="row.credits_required"> · {{ row.credits_required }} cr</template>
        </span>
      </template>
      <template #cell-is_active="{ row }"><StatusBadge :status="row.is_active ? 'active' : 'archived'" /></template>
      <template #cell-actions="{ row }">
        <div v-if="canManage" class="flex flex-wrap items-center justify-end gap-x-4 gap-y-1">
          <IconButton :icon="Pencil" :href="`/programs/${row.id}/edit`" :label="`Edit ${row.code}`" />
          <button v-if="row.is_active" type="button" class="text-small font-semibold text-warning hover:underline" @click="archive(row)">Archive</button>
          <button v-else type="button" class="text-small font-semibold text-success hover:underline" @click="reactivate(row)">Reactivate</button>
          <IconButton :icon="Trash2" variant="danger" :label="`Delete ${row.code}`" @click="destroy(row)" />
        </div>
        <span v-else class="block text-right text-caption text-muted dark:text-dark-muted">Read only</span>
      </template>
    </BaseTable>

    <Pagination :links="programs.meta?.links ?? []" />

    <BaseModal v-model="showCreate" title="New program" size="md">
      <form class="space-y-5" @submit.prevent="submitCreate">
        <ProgramForm :form="createForm" :faculties="faculties" :departments="departments" :degree-levels="degreeLevels" />

        <ErrorAlert v-if="Object.keys(createForm.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="createForm.processing">Create program</BaseButton>
          <BaseButton variant="ghost" @click="showCreate = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
