<script setup>
import IconButton from '../../components/IconButton.vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { FunnelX, Plus, Search, Settings2 } from '@lucide/vue'
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
import StudentForm from '../../components/students/StudentForm.vue'

const props = defineProps({
  students: { type: Object, required: true },
  departments: { type: Object, required: true },
  programs: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  genders: { type: Array, default: () => [] },
  unlinkedAccounts: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
// Department Admin reads only; write controls are hidden (backend still enforces).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))
const departments = computed(() => props.departments?.data ?? [])

const search = ref(props.filters.search ?? '')
const departmentId = ref(props.filters.department_id ?? '')
const programId = ref(props.filters.program_id ?? '')
const status = ref(props.filters.status ?? '')

const departmentOptions = computed(() => departments.value.map((department) => ({ value: department.id, label: department.name })))
const programOptions = computed(() => props.programs.map((program) => ({ value: program.id, label: `${program.code} — ${program.name}` })))
const statusOptions = computed(() => props.statuses.map((value) => ({ value, label: value.charAt(0).toUpperCase() + value.slice(1) })))
const hasFilters = computed(() => Boolean(search.value || departmentId.value || programId.value || status.value))

const applyFilters = () => {
  const filters = { department_id: departmentId.value || undefined, program_id: programId.value || undefined, status: status.value || undefined }
  router.get('/students', { search: search.value || undefined, filters: Object.values(filters).some((v) => v !== undefined) ? filters : undefined }, { preserveState: true, replace: true })
}
const clearFilters = () => {
  search.value = ''
  departmentId.value = ''
  programId.value = ''
  status.value = ''
  router.get('/students', {}, { preserveState: true, replace: true })
}

const columns = [
  { key: 'student_number', label: 'Student ID' },
  { key: 'name', label: 'Student' },
  { key: 'program', label: 'Program' },
  { key: 'enrollment_date', label: 'Enrolled' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const showCreate = ref(false)
const createForm = useForm({
  user_id: '', email: '', phone: '', password: '', password_confirmation: '',
  student_number: '', first_name: '', last_name: '', gender: '', date_of_birth: '', national_id: '',
  address: '', emergency_contact_name: '', emergency_contact_phone: '', enrollment_date: '', program_id: '',
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
    .post('/students', { preserveScroll: true, onSuccess: () => { showCreate.value = false } })
</script>

<template>
  <Head title="Students - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="People" title="Students" description="Student profiles, their login account, status and program. Enrollments and grades arrive with their modules.">
      <template v-if="canManage" #actions>
        <IconButton :icon="Plus" size="md" variant="primary" label="New student" @click="openCreate" />
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_2fr_2fr_1fr_auto] lg:items-end" @submit.prevent="applyFilters">
        <BaseInput v-model="search" label="Search" placeholder="Name, student ID, email" />
        <BaseSelect v-model="departmentId" label="Department" :options="departmentOptions" placeholder="All departments" />
        <BaseSelect v-model="programId" label="Program" :options="programOptions" placeholder="All programs" />
        <BaseSelect v-model="status" label="Status" :options="statusOptions" placeholder="All" />
        <div class="flex gap-2">
          <IconButton :icon="Search" type="submit" size="md" label="Apply filters" />
          <IconButton v-if="hasFilters" :icon="FunnelX" size="md" label="Clear filters" @click="clearFilters" />
        </div>
      </form>
    </BaseCard>

    <BaseTable
      :columns="columns"
      :rows="students.data"
      caption="Students"
      :empty-title="hasFilters ? 'No students match your filters' : 'No students yet'"
      :empty-description="hasFilters ? 'Try different filters or clear them.' : 'Add the first student and their login account.'"
    >
      <template #empty-action>
        <IconButton v-if="!hasFilters && canManage" :icon="Plus" size="md" variant="primary" label="New student" @click="openCreate" />
      </template>
      <template #cell-student_number="{ row }"><span class="font-mono text-small">{{ row.student_number }}</span></template>
      <template #cell-name="{ row }">
        <p class="font-medium">{{ row.full_name }}</p>
        <p class="max-w-56 truncate text-caption text-muted dark:text-dark-muted">{{ row.user?.email }}</p>
      </template>
      <template #cell-program="{ row }">
        <span v-if="row.current_program?.program" class="text-small">{{ row.current_program.program.code }}</span>
        <span v-else class="text-muted dark:text-dark-muted">—</span>
      </template>
      <template #cell-enrollment_date="{ row }"><span class="whitespace-nowrap text-muted dark:text-dark-muted">{{ row.enrollment_date ?? '—' }}</span></template>
      <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
      <template #cell-actions="{ row }">
        <div v-if="canManage" class="flex justify-end"><IconButton :icon="Settings2" :href="`/students/${row.id}/edit`" :label="`Manage ${row.full_name}`" /></div>
        <span v-else class="block text-right text-caption text-muted dark:text-dark-muted">Read only</span>
      </template>
    </BaseTable>

    <Pagination :links="students.meta?.links ?? []" />

    <BaseModal v-model="showCreate" title="New student" size="lg">
      <form class="space-y-5" @submit.prevent="submitCreate">
        <StudentForm :form="createForm" mode="create" :departments="departments" :programs="programs" :genders="genders" :unlinked-accounts="unlinkedAccounts" />
        <ErrorAlert v-if="Object.keys(createForm.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />
        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="createForm.processing">Create student</BaseButton>
          <BaseButton variant="ghost" @click="showCreate = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
