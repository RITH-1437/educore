<script setup>
import IconButton from '../../components/IconButton.vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { FunnelX, Pencil, Plus, Search, Trash2, UserCheck, UserX } from '@lucide/vue'
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
import LecturerForm from '../../components/lecturers/LecturerForm.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  lecturers: { type: Object, required: true },
  faculties: { type: Object, required: true },
  departments: { type: Object, required: true },
  employmentTypes: { type: Array, default: () => [] },
  unlinkedAccounts: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const { confirm } = useConfirm()

// Faculty Admin may read lecturers but not change them; write controls are
// hidden rather than left to fail with a 403 (`skills/lecturer-management/SKILL.md` §8).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))

const faculties = computed(() => props.faculties?.data ?? [])
const departments = computed(() => props.departments?.data ?? [])
const typeLabel = (type) => type.replace('_', ' ').replace(/^./, (c) => c.toUpperCase())

const search = ref(props.filters.search ?? '')
const facultyId = ref(props.filters.faculty_id ?? '')
const employmentType = ref(props.filters.employment_type ?? '')
const activeState = ref(props.filters.is_active === null || props.filters.is_active === undefined ? '' : props.filters.is_active ? '1' : '0')

const facultyOptions = computed(() => faculties.value.map((faculty) => ({ value: faculty.id, label: faculty.name })))
const typeOptions = computed(() => props.employmentTypes.map((type) => ({ value: type, label: typeLabel(type) })))
const stateOptions = [
  { value: '1', label: 'Active' },
  { value: '0', label: 'Inactive' },
]

const hasFilters = computed(() => Boolean(search.value || facultyId.value || employmentType.value || activeState.value !== ''))

const applyFilters = () => {
  const filters = {
    faculty_id: facultyId.value || undefined,
    employment_type: employmentType.value || undefined,
    is_active: activeState.value === '' ? undefined : activeState.value,
  }
  router.get(
    '/lecturers',
    { search: search.value || undefined, filters: Object.values(filters).some((v) => v !== undefined) ? filters : undefined },
    { preserveState: true, replace: true },
  )
}

const clearFilters = () => {
  search.value = ''
  facultyId.value = ''
  employmentType.value = ''
  activeState.value = ''
  router.get('/lecturers', {}, { preserveState: true, replace: true })
}

const columns = [
  { key: 'staff_number', label: 'Staff no.' },
  { key: 'name', label: 'Lecturer' },
  { key: 'department', label: 'Department' },
  { key: 'employment_type', label: 'Type' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const showCreate = ref(false)
const createForm = useForm({
  user_id: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  staff_number: '',
  first_name: '',
  last_name: '',
  title: '',
  department_id: '',
  position: '',
  specialization: '',
  employment_type: 'full_time',
})

const openCreate = () => {
  createForm.reset()
  createForm.clearErrors()
  showCreate.value = true
}

// Send only the fields of the chosen account mode.
const submitCreate = () =>
  createForm
    .transform((data) => {
      const payload = { ...data }
      if (payload.user_id) {
        delete payload.email
        delete payload.password
        delete payload.password_confirmation
      } else {
        delete payload.user_id
      }
      return payload
    })
    .post('/lecturers', {
      preserveScroll: true,
      onSuccess: () => {
        showCreate.value = false
        createForm.reset()
      },
    })

const deactivate = async (lecturer) => {
  if (await confirm({ title: 'Deactivate lecturer?', message: `Deactivate ${lecturer.full_name}? Their profile and account are marked inactive; nothing is deleted.`, confirmLabel: 'Deactivate' })) {
    router.post(`/lecturers/${lecturer.id}/deactivate`, {}, { preserveScroll: true })
  }
}

const reactivate = (lecturer) => router.post(`/lecturers/${lecturer.id}/reactivate`, {}, { preserveScroll: true })

const destroy = async (lecturer) => {
  if (await confirm({ title: 'Delete lecturer profile?', message: `Delete the profile of ${lecturer.full_name}? Their account is kept but marked inactive. This is refused while they are assigned to sections.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/lecturers/${lecturer.id}`, { preserveScroll: true })
  }
}
</script>

<template>
  <Head title="Lecturers - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="People" title="Lecturers" description="Teaching staff profiles, their home department and their login account. Teaching assignments arrive with sections.">
      <template v-if="canManage" #actions>
        <IconButton :icon="Plus" size="md" variant="primary" label="New lecturer" @click="openCreate" />
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_2fr_1fr_1fr_auto] lg:items-end" @submit.prevent="applyFilters">
        <BaseInput v-model="search" label="Search" placeholder="Name, staff no., email" />
        <BaseSelect v-model="facultyId" label="Faculty" :options="facultyOptions" placeholder="All faculties" />
        <BaseSelect v-model="employmentType" label="Type" :options="typeOptions" placeholder="All types" />
        <BaseSelect v-model="activeState" label="Status" :options="stateOptions" placeholder="All" />
        <div class="flex gap-2">
          <IconButton :icon="Search" type="submit" size="md" label="Apply filters" />
          <IconButton v-if="hasFilters" :icon="FunnelX" size="md" label="Clear filters" @click="clearFilters" />
        </div>
      </form>
    </BaseCard>

    <BaseTable
      :columns="columns"
      :rows="lecturers.data"
      caption="Lecturers"
      :empty-title="hasFilters ? 'No lecturers match your filters' : 'No lecturers yet'"
      :empty-description="hasFilters ? 'Try different filters or clear them.' : 'Add the first lecturer and their login account.'"
    >
      <template #empty-action>
        <IconButton v-if="!hasFilters && canManage" :icon="Plus" size="md" variant="primary" label="New lecturer" @click="openCreate" />
      </template>
      <template #cell-staff_number="{ row }"><span class="font-mono text-small">{{ row.staff_number }}</span></template>
      <template #cell-name="{ row }">
        <p class="font-medium">{{ row.full_name }}</p>
        <p class="max-w-56 truncate text-caption text-muted dark:text-dark-muted">{{ row.user?.email }}<template v-if="row.position"> · {{ row.position }}</template></p>
      </template>
      <template #cell-department="{ row }">
        <span class="text-small">{{ row.department?.code }}</span>
        <span v-if="row.department?.faculty" class="text-caption text-muted dark:text-dark-muted"> · {{ row.department.faculty.code }}</span>
      </template>
      <template #cell-employment_type="{ row }"><span class="whitespace-nowrap">{{ typeLabel(row.employment_type) }}</span></template>
      <template #cell-is_active="{ row }"><StatusBadge :status="row.is_active ? 'active' : 'inactive'" /></template>
      <template #cell-actions="{ row }">
        <div v-if="canManage" class="flex flex-wrap items-center justify-end gap-1">
          <IconButton :icon="Pencil" :href="`/lecturers/${row.id}/edit`" label="Edit lecturer" />
          <IconButton v-if="row.is_active" :icon="UserX" label="Deactivate lecturer" @click="deactivate(row)" />
          <IconButton v-else :icon="UserCheck" variant="success" label="Reactivate lecturer" @click="reactivate(row)" />
          <IconButton :icon="Trash2" variant="danger" label="Delete lecturer" @click="destroy(row)" />
        </div>
        <span v-else class="block text-right text-caption text-muted dark:text-dark-muted">Read only</span>
      </template>
    </BaseTable>

    <Pagination :links="lecturers.meta?.links ?? []" />

    <BaseModal v-model="showCreate" title="New lecturer" size="lg">
      <form class="space-y-5" @submit.prevent="submitCreate">
        <LecturerForm
          :form="createForm"
          mode="create"
          :faculties="faculties"
          :departments="departments"
          :employment-types="employmentTypes"
          :unlinked-accounts="unlinkedAccounts"
        />

        <ErrorAlert v-if="Object.keys(createForm.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="createForm.processing">Create lecturer</BaseButton>
          <BaseButton variant="ghost" @click="showCreate = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
