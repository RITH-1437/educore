<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Building2, Search } from '@lucide/vue'
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
  faculties: { type: Object, required: true },
  departments: { type: Array, required: true },
  universities: { type: Object, required: true },
  filters: { type: Object, default: () => ({ search: '', university_id: null, is_active: null }) },
})

const page = usePage()

// Faculty Admin may read the structure but not change it, so every write
// control is hidden rather than left to fail with a 403
// (`skills/faculty-department/SKILL.md` §8).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))

const search = ref(props.filters.search ?? '')
const universityId = ref(props.filters.university_id ?? '')
const isActive = ref(props.filters.is_active === null ? '' : String(props.filters.is_active))

const facultyForm = useForm({
  university_id: '',
  code: '',
  name: '',
  dean_name: '',
  description: '',
})
const departmentForm = useForm({
  faculty_id: null,
  code: '',
  name: '',
  head_name: '',
  description: '',
})

const showFacultyModal = ref(false)
const showDepartmentModal = ref(false)
const expanded = ref(new Set())

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'Faculty' },
  { key: 'dean_name', label: 'Dean' },
  { key: 'departments_count', label: 'Departments', align: 'center' },
  { key: 'is_active', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const universityOptions = computed(() =>
  (props.universities?.data ?? []).map((university) => ({
    value: university.id,
    label: `${university.code} — ${university.name}`,
  })),
)

const facultyOptions = computed(() =>
  (props.faculties?.data ?? []).map((faculty) => ({
    value: faculty.id,
    label: `${faculty.code} — ${faculty.name}`,
  })),
)

const activeFilterOptions = [
  { value: '', label: 'All states' },
  { value: 'true', label: 'Active' },
  { value: 'false', label: 'Archived' },
]

const toggleExpanded = (facultyId) => {
  const next = new Set(expanded.value)
  if (next.has(facultyId)) next.delete(facultyId)
  else next.add(facultyId)
  expanded.value = next
}

const departmentsOf = (facultyId) => props.departments.filter((department) => department.faculty_id === facultyId)

const applyFilters = () =>
  router.get(
    '/faculties',
    {
      search: search.value || undefined,
      filters: {
        university_id: universityId.value || undefined,
        is_active: isActive.value === '' ? undefined : isActive.value,
      },
    },
    { preserveState: true, replace: true },
  )

const openFacultyModal = () => {
  facultyForm.defaults({
    university_id: universityId.value || universityOptions.value[0]?.value || '',
    code: '',
    name: '',
    dean_name: '',
    description: '',
  })
  facultyForm.reset()
  showFacultyModal.value = true
}

const openDepartmentModal = (facultyId) => {
  departmentForm.defaults({
    faculty_id: facultyId ?? null,
    code: '',
    name: '',
    head_name: '',
    description: '',
  })
  departmentForm.reset()
  showDepartmentModal.value = true
}

const submitFaculty = () =>
  facultyForm.post('/faculties', {
    preserveScroll: true,
    onSuccess: () => showFacultyModal.value = false,
  })

const submitDepartment = () => {
  if (!departmentForm.faculty_id) {
    departmentForm.setError('faculty_id', 'Choose the faculty that owns this department.')
    return
  }

  departmentForm.post(`/faculties/${departmentForm.faculty_id}/departments`, {
    preserveScroll: true,
    onSuccess: () => showDepartmentModal.value = false,
  })
}

const { confirm } = useConfirm()

const archiveFaculty = async (faculty) => {
  if (await confirm({ title: 'Archive faculty?', message: `Archive "${faculty.name}"? It stays visible but inactive, and its departments keep their parent.`, confirmLabel: 'Archive' })) {
    router.post(`/faculties/${faculty.id}/archive`)
  }
}

const reactivateFaculty = (faculty) => router.post(`/faculties/${faculty.id}/reactivate`)

const deleteFaculty = async (faculty) => {
  if (await confirm({ title: 'Delete faculty?', message: `Delete "${faculty.name}"? This is refused while it still has departments.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/faculties/${faculty.id}`)
  }
}

const archiveDepartment = async (department) => {
  if (await confirm({ title: 'Archive department?', message: `Archive "${department.name}"?`, confirmLabel: 'Archive' })) {
    router.post(`/faculties/${department.faculty_id}/departments/${department.id}/archive`)
  }
}

const reactivateDepartment = (department) =>
  router.post(`/faculties/${department.faculty_id}/departments/${department.id}/reactivate`)

const deleteDepartment = async (department) => {
  if (await confirm({ title: 'Delete department?', message: `Delete "${department.name}"? This is refused while programs, courses or lecturers reference it.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/faculties/${department.faculty_id}/departments/${department.id}`)
  }
}
</script>

<template>
  <Head title="Faculties & departments - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="University structure" title="Faculties & departments" description="The top of the academic hierarchy. Departments belong to exactly one faculty and may be archived rather than deleted.">
      <template v-if="canManage" #actions>
        <BaseButton variant="secondary" @click="openDepartmentModal(null)">
          <Building2 class="h-4 w-4" aria-hidden="true" /> New department
        </BaseButton>
        <BaseButton @click="openFacultyModal">+ New faculty</BaseButton>
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="flex flex-col gap-3 lg:flex-row lg:items-end" @submit.prevent="applyFilters">
        <BaseInput v-model="search" label="Search faculties" placeholder="Code or name" class="w-full lg:max-w-xs" />
        <BaseSelect v-model="universityId" label="University" :options="universityOptions" placeholder="All universities" class="w-full lg:max-w-xs" />
        <BaseSelect v-model="isActive" label="State" :options="activeFilterOptions" />
        <BaseButton type="submit" variant="secondary"><Search class="h-4 w-4" aria-hidden="true" /> Search</BaseButton>
      </form>
    </BaseCard>

    <div class="space-y-4">
      <BaseTable
        :columns="columns"
        :rows="faculties.data"
        empty-title="No faculties found"
        empty-description="Create a faculty to start building the academic structure."
      >
        <template #cell-code="{ row }">
          <span class="font-semibold text-ink dark:text-dark-ink">{{ row.code }}</span>
        </template>
        <template #cell-name="{ row }">
          <div>
            <p class="font-medium text-ink dark:text-dark-ink">{{ row.name }}</p>
            <p v-if="row.university" class="text-caption text-muted dark:text-dark-muted">{{ row.university.code }}</p>
          </div>
        </template>
        <template #cell-dean_name="{ row }">
          <span class="text-muted dark:text-dark-muted">{{ row.dean_name ?? '—' }}</span>
        </template>
        <template #cell-departments_count="{ row }">
          <button
            type="button"
            class="rounded-md px-2 py-1 font-semibold text-primary hover:underline dark:text-dark-primary"
            :aria-expanded="expanded.has(row.id)"
            @click="toggleExpanded(row.id)"
          >
            {{ row.departments_count ?? 0 }}
          </button>
        </template>
        <template #cell-is_active="{ row }">
          <StatusBadge :status="row.is_active ? 'active' : 'archived'" />
        </template>
        <template #cell-actions="{ row }">
          <div v-if="canManage" class="flex justify-end gap-3">
            <Link :href="`/faculties/${row.id}/edit`" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary">Edit</Link>
            <button type="button" class="text-small font-semibold text-primary hover:underline dark:text-dark-primary" @click="openDepartmentModal(row.id)">Add dept</button>
            <button v-if="row.is_active" type="button" class="text-small font-semibold text-warning hover:underline" @click="archiveFaculty(row)">Archive</button>
            <button v-else type="button" class="text-small font-semibold text-success hover:underline" @click="reactivateFaculty(row)">Reactivate</button>
            <button type="button" class="text-small font-semibold text-error hover:underline" @click="deleteFaculty(row)">Delete</button>
          </div>
          <span v-else class="block text-right text-caption text-muted dark:text-dark-muted">Read only</span>
        </template>
      </BaseTable>

      <Pagination :links="faculties.meta?.links ?? []" />

      <section
        v-for="faculty in faculties.data.filter((row) => expanded.has(row.id))"
        :key="`departments-${faculty.id}`"
        class="rounded-xl border border-border-default bg-surface p-4 shadow-sm dark:border-dark-border dark:bg-dark-surface"
      >
        <h3 class="text-h4 font-semibold text-ink dark:text-dark-ink">{{ faculty.code }} departments</h3>
        <p class="mt-1 text-small text-muted dark:text-dark-muted">
          Department names are unique within this faculty.
        </p>
        <ul v-if="departmentsOf(faculty.id).length" class="mt-4 space-y-2">
          <li
            v-for="department in departmentsOf(faculty.id)"
            :key="department.id"
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border-default px-3 py-2 dark:border-dark-border"
          >
            <div class="flex flex-wrap items-center gap-2">
              <span class="font-semibold text-ink dark:text-dark-ink">{{ department.code }}</span>
              <span class="text-small text-muted dark:text-dark-muted">{{ department.name }}</span>
              <StatusBadge :status="department.is_active ? 'active' : 'archived'" />
            </div>
            <div v-if="canManage" class="flex gap-3">
              <button
                v-if="department.is_active"
                type="button"
                class="text-small font-semibold text-warning hover:underline"
                @click="archiveDepartment(department)"
              >
                Archive
              </button>
              <button
                v-else
                type="button"
                class="text-small font-semibold text-success hover:underline"
                @click="reactivateDepartment(department)"
              >
                Reactivate
              </button>
              <button
                type="button"
                class="text-small font-semibold text-error hover:underline"
                @click="deleteDepartment(department)"
              >
                Delete
              </button>
            </div>
            <span v-else class="text-caption text-muted dark:text-dark-muted">Read only</span>
          </li>
        </ul>
        <p v-else class="mt-4 text-small text-muted dark:text-dark-muted">No departments in this faculty yet.</p>
      </section>
    </div>

    <BaseModal v-model="showFacultyModal" title="New faculty" size="md">
      <form id="faculty-form" class="space-y-5" @submit.prevent="submitFaculty">
        <BaseSelect
          v-model="facultyForm.university_id"
          label="University"
          :options="universityOptions"
          :error="facultyForm.errors.university_id"
          required
        />
        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="facultyForm.code" label="Code" placeholder="ENG" :error="facultyForm.errors.code" required />
          <BaseInput v-model="facultyForm.name" label="Name" placeholder="Faculty of Engineering" :error="facultyForm.errors.name" required />
        </div>
        <BaseInput v-model="facultyForm.dean_name" label="Dean" placeholder="Dr. Sokha Chan" :error="facultyForm.errors.dean_name" />
        <BaseTextarea v-model="facultyForm.description" label="Description" rows="3" :error="facultyForm.errors.description" />
        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="facultyForm.processing">Create faculty</BaseButton>
          <BaseButton variant="ghost" @click="showFacultyModal = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>

    <BaseModal v-model="showDepartmentModal" title="New department" size="md">
      <form id="department-form" class="space-y-5" @submit.prevent="submitDepartment">
        <BaseSelect
          v-model="departmentForm.faculty_id"
          label="Faculty"
          :options="facultyOptions"
          :error="departmentForm.errors.faculty_id"
          required
        />
        <div class="grid gap-5 sm:grid-cols-2">
          <BaseInput v-model="departmentForm.code" label="Code" placeholder="CSE" :error="departmentForm.errors.code" required />
          <BaseInput v-model="departmentForm.name" label="Name" placeholder="Department of Computer Science" :error="departmentForm.errors.name" required />
        </div>
        <BaseInput v-model="departmentForm.head_name" label="Head" placeholder="Dr. Dara Lim" :error="departmentForm.errors.head_name" />
        <BaseTextarea v-model="departmentForm.description" label="Description" rows="3" :error="departmentForm.errors.description" />
        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="departmentForm.processing">Create department</BaseButton>
          <BaseButton variant="ghost" @click="showDepartmentModal = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>