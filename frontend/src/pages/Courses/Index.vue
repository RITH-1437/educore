<script setup>
import IconButton from '../../components/IconButton.vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useLiveFilters } from '../../composables/useLiveFilters'
import { Archive, ArchiveRestore, FunnelX, Pencil, Plus, Trash2 } from '@lucide/vue'
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
import CourseForm from '../../components/courses/CourseForm.vue'
import { useConfirm } from '../../composables/useConfirm'

const props = defineProps({
  courses: { type: Object, required: true },
  departments: { type: Object, required: true },
  programs: { type: Array, default: () => [] },
  levels: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const { confirm } = useConfirm()

// Department Admin may read the catalog but not change it; write controls are
// hidden rather than left to fail with a 403 (`skills/course-management/SKILL.md` §8).
const canManage = computed(() => ['super-admin', 'university-admin'].includes(page.props.auth?.user?.role?.slug ?? ''))

const departments = computed(() => props.departments?.data ?? [])
const titleCase = (value) => value.charAt(0).toUpperCase() + value.slice(1)

const search = ref(props.filters.search ?? '')
const departmentId = ref(props.filters.department_id ?? '')
const programId = ref(props.filters.program_id ?? '')
const status = ref(props.filters.status ?? '')
const level = ref(props.filters.course_level ?? '')

const departmentOptions = computed(() => departments.value.map((department) => ({ value: department.id, label: department.name })))
const programOptions = computed(() => props.programs.map((program) => ({ value: program.id, label: `${program.code} — ${program.name}` })))
const statusOptions = computed(() => props.statuses.map((value) => ({ value, label: titleCase(value) })))
const levelOptions = computed(() => props.levels.map((value) => ({ value, label: titleCase(value) })))

const hasFilters = computed(() => Boolean(search.value || departmentId.value || programId.value || status.value || level.value))

const applyFilters = (options = {}) => {
  const filters = {
    department_id: departmentId.value || undefined,
    program_id: programId.value || undefined,
    status: status.value || undefined,
    course_level: level.value || undefined,
  }
  router.get(
    '/courses',
    { search: search.value || undefined, filters: Object.values(filters).some((v) => v !== undefined) ? filters : undefined },
    { preserveState: true, replace: true, ...options },
  )
}

const clearFilters = () => {
  search.value = ''
  departmentId.value = ''
  programId.value = ''
  status.value = ''
  level.value = ''
  applyNow()
}

const columns = [
  { key: 'code', label: 'Code' },
  { key: 'name', label: 'Course' },
  { key: 'course_level', label: 'Level' },
  { key: 'credits', label: 'Credits', align: 'center' },
  { key: 'links', label: 'Links' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: 'Actions', align: 'right' },
]

const showCreate = ref(false)
const createForm = useForm({
  department_id: '',
  code: '',
  name: '',
  credits: 3,
  lecture_hours: '',
  lab_hours: '',
  description: '',
  course_level: '',
  status: 'active',
})

const openCreate = () => {
  createForm.reset()
  createForm.clearErrors()
  showCreate.value = true
}

const submitCreate = () =>
  createForm.post('/courses', {
    preserveScroll: true,
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
    },
  })

const archive = async (course) => {
  if (await confirm({ title: 'Archive course?', message: `Archive "${course.name}"? It stays in existing curricula but cannot be added to new ones or used as a prerequisite.`, confirmLabel: 'Archive' })) {
    router.post(`/courses/${course.id}/archive`, {}, { preserveScroll: true })
  }
}

const reactivate = (course) => router.post(`/courses/${course.id}/reactivate`, {}, { preserveScroll: true })

const destroy = async (course) => {
  if (await confirm({ title: 'Delete course?', message: `Delete "${course.name}"? This is refused while a program curriculum, another course's prerequisites or an offering uses it — archive it instead.`, confirmLabel: 'Delete', destructive: true })) {
    router.delete(`/courses/${course.id}`, { preserveScroll: true })
  }
}
// Soft search: the list follows the filters as they change — typed text after a
// short pause, picked options at once — so there is no search button.
const { applyNow, searching } = useLiveFilters(applyFilters, { text: [search], choices: [departmentId, programId, status, level] })
</script>

<template>
  <Head title="Courses - EduCore" />
  <div class="space-y-6">
    <PageHeader eyebrow="Academics" title="Courses" description="The course catalog: credits, prerequisites and the programs each course belongs to. Offerings and sections arrive with the timetable modules.">
      <template v-if="canManage" #actions>
        <IconButton :icon="Plus" size="md" variant="primary" label="New course" @click="openCreate" />
      </template>
    </PageHeader>

    <BaseCard padding="sm">
      <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 lg:items-end xl:grid-cols-[2fr_2fr_2fr_1fr_1fr_auto]" @submit.prevent="applyNow">
        <BaseInput v-model="search" :loading="searching" label="Search" placeholder="Code or name" />
        <BaseSelect v-model="departmentId" label="Department" :options="departmentOptions" placeholder="All departments" />
        <BaseSelect v-model="programId" label="Program" :options="programOptions" placeholder="All programs" />
        <BaseSelect v-model="level" label="Level" :options="levelOptions" placeholder="All levels" />
        <BaseSelect v-model="status" label="Status" :options="statusOptions" placeholder="All" />
        <div class="flex gap-1">
          <IconButton v-if="hasFilters" :icon="FunnelX" size="md" label="Clear filters" @click="clearFilters" />
        </div>
      </form>
    </BaseCard>

    <BaseTable
      :columns="columns"
      :rows="courses.data"
      caption="Courses"
      :empty-title="hasFilters ? 'No courses match your filters' : 'No courses yet'"
      :empty-description="hasFilters ? 'Try different filters or clear them.' : 'Create the first course under a department.'"
    >
      <template #empty-action>
        <IconButton v-if="!hasFilters && canManage" :icon="Plus" size="md" variant="primary" label="New course" @click="openCreate" />
      </template>
      <template #cell-code="{ row }"><span class="font-semibold">{{ row.code }}</span></template>
      <template #cell-name="{ row }">
        <p class="font-medium">{{ row.name }}</p>
        <p class="text-caption text-muted dark:text-dark-muted">{{ row.department?.name }}</p>
      </template>
      <template #cell-course_level="{ row }"><span class="capitalize">{{ row.course_level ?? '—' }}</span></template>
      <template #cell-credits="{ row }"><span class="font-semibold tabular-nums">{{ row.credits }}</span></template>
      <template #cell-links="{ row }">
        <span class="whitespace-nowrap text-caption text-muted dark:text-dark-muted">
          {{ row.prerequisites_count ?? 0 }} prereq · {{ row.programs_count ?? 0 }} {{ (row.programs_count ?? 0) === 1 ? 'program' : 'programs' }}
        </span>
      </template>
      <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
      <template #cell-actions="{ row }">
        <div v-if="canManage" class="flex flex-wrap items-center justify-end gap-1">
          <IconButton :icon="Pencil" :href="`/courses/${row.id}/edit`" :label="`Edit ${row.code}`" />
          <IconButton v-if="row.status !== 'archived'" :icon="Archive" :label="`Archive ${row.code}`" @click="archive(row)" />
          <IconButton v-else :icon="ArchiveRestore" variant="success" :label="`Reactivate ${row.code}`" @click="reactivate(row)" />
          <IconButton :icon="Trash2" variant="danger" :label="`Delete ${row.code}`" @click="destroy(row)" />
        </div>
        <span v-else class="block text-right text-caption text-muted dark:text-dark-muted">Read only</span>
      </template>
    </BaseTable>

    <Pagination :links="courses.meta?.links ?? []" />

    <BaseModal v-model="showCreate" title="New course" size="lg">
      <form class="space-y-5" @submit.prevent="submitCreate">
        <CourseForm :form="createForm" :departments="departments" :levels="levels" />

        <ErrorAlert v-if="Object.keys(createForm.errors).length" title="Check the form" message="Correct the highlighted fields and try again." />

        <div class="flex items-center gap-3">
          <BaseButton type="submit" :loading="createForm.processing">Create course</BaseButton>
          <BaseButton variant="ghost" @click="showCreate = false">Cancel</BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
